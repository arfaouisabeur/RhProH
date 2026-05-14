<?php

namespace App\Service;

use App\Entity\CongeTt;
use App\Entity\Employe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * SmsService — Envoie des SMS d'alerte via Twilio.
 *
 * Installation : composer require twilio/sdk
 *
 * Conditions d'envoi (comme en Java) :
 *  - Congé maladie, OU
 *  - Date de début dans les 2 prochains jours (urgente)
 *
 * Limite compte Trial Twilio : SMS uniquement vers numéros vérifiés.
 * Format tunisien : +216XXXXXXXX
 */
class SmsService
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params)
    {
        $this->em = $em;
        $this->accountSid = $params->get('twilio_account_sid');
        $this->authToken = $params->get('twilio_auth_token');
        $this->fromNumber = $params->get('twilio_from_number');
    }

    /**
     * Envoie un SMS d'alerte à l'employé si le congé est de type maladie
     * OU si la date de début est dans les 2 prochains jours (urgence).
     * Fonctionne pour décision ACCEPTÉE et REFUSÉE.
     *
     * @param CongeTt $conge   La demande de congé concernée
     * @param string  $statut  'approuvé' ou 'refusé'
     */
    public function envoyerAlerteConge(CongeTt $conge, string $statut): void
    {
        error_log('[SmsService] ===== Début envoi SMS =====');
        error_log('[SmsService] Statut: ' . $statut);
        error_log('[SmsService] Type congé: ' . ($conge->getTypeConge() ?? 'null'));

        // Récupérer le téléphone de l\'employé
        $telephone = $this->getTelephoneEmploye($conge->getEmploye());
        if (!$telephone) {
            error_log('[SmsService] ❌ Numéro de téléphone introuvable pour l\'employé.');
            return;
        }
        error_log('[SmsService] ✅ Téléphone trouvé (brut): ' . $telephone);

        // Formater le numéro (indicatif Tunisie +216)
        $telephone = $this->formatTelephone($telephone);
        error_log('[SmsService] ✅ Téléphone formaté: ' . $telephone);

        // Label lisible pour le message
        $labelStatut = ($statut === 'approuvé') ? 'ACCEPTEE' : 'REFUSEE';

        $message = sprintf(
            "ALERTE RH\nVotre conge (%s) du %s a ete %s.",
            $conge->getTypeConge(),
            $conge->getDateDebut()?->format('d/m/Y') ?? '-',
            $labelStatut
        );

        error_log('[SmsService] Message: ' . str_replace("\n", ' | ', $message));
        error_log('[SmsService] From: ' . $this->fromNumber);
        error_log('[SmsService] SID: ' . substr($this->accountSid, 0, 6) . '...');
        $this->sendSms($telephone, $message);
    }

    /**
     * Méthode principale d'envoi SMS via Twilio SDK.
     *
     * @throws \RuntimeException si l'envoi échoue
     */
    public function sendSms(string $to, string $message): void
    {
        if (!class_exists(\Twilio\Rest\Client::class)) {
            error_log('[SmsService] ❌ SDK Twilio non installé. Commande : composer require twilio/sdk');
            return;
        }

        error_log('[SmsService] ✅ SDK Twilio trouvé, tentative d\'envoi...');
        error_log('[SmsService] -> To: ' . $to);
        error_log('[SmsService] -> From: ' . $this->fromNumber);

        try {
            // Fix SSL Windows : CurlClient Twilio natif + cacert.pem
            $cacert = __DIR__ . '/../../cacert.pem';
            $httpClient = null;

            if (file_exists($cacert) && class_exists(\Twilio\Http\CurlClient::class)) {
                $httpClient = new \Twilio\Http\CurlClient([
                    CURLOPT_CAINFO         => $cacert,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_TIMEOUT        => 30,
                ]);
                error_log('[SmsService] ✅ CurlClient avec cacert.pem configuré.');
            } else {
                error_log('[SmsService] ⚠️ cacert.pem introuvable — SSL par défaut.');
            }

            $client = new \Twilio\Rest\Client(
                $this->accountSid,
                $this->authToken,
                null,
                null,
                $httpClient
            );

            $msg = $client->messages->create($to, [
                'from' => $this->fromNumber,
                'body' => $message,
            ]);
            error_log('[SmsService] ✅ SMS envoyé ! SID message: ' . $msg->sid . ' | Statut: ' . $msg->status);
        } catch (\Exception $e) {
            error_log('[SmsService] ❌ Erreur Twilio : ' . $e->getMessage());
            error_log('[SmsService] Code HTTP : ' . $e->getCode());
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si le congé est "urgent" (maladie ou date dans les 2 prochains jours).
     */
    private function estUrgent(CongeTt $conge): bool
    {
        $today = new \DateTime('today');
        $debut = $conge->getDateDebut();

        // Date urgente : aujourd'hui, demain ou après-demain
        $isUrgentDate = $debut !== null && (
            $debut <= (new \DateTime('+2 days'))
            && $debut >= $today
        );

        // Type urgent : maladie
        $isUrgentType = str_contains(strtolower($conge->getTypeConge() ?? ''), 'maladie');

        return $isUrgentDate || $isUrgentType;
    }

    /**
     * Récupère le numéro de téléphone de l'employé via sa relation User.
     */
    private function getTelephoneEmploye(?Employe $employe): ?string
    {
        if ($employe === null) return null;
        $user = $employe->getUser();
        if ($user === null) return null;

        // La méthode getTelephone() doit exister dans l'entité User
        return method_exists($user, 'getTelephone') ? $user->getTelephone() : null;
    }

    /**
     * Formate un numéro tunisien : si pas de +, ajoute +216.
     */
    private function formatTelephone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone); // retirer espaces
        if (!str_starts_with($phone, '+')) {
            $phone = '+216' . ltrim($phone, '0');
        }
        return $phone;
    }
}