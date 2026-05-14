<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\DemandeServiceRepository;

#[ORM\Entity(repositoryClass: DemandeServiceRepository::class)]
#[ORM\Table(name: "demande_service")]
class DemandeService
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: false)]
    private string $titre;

    #[ORM\Column(nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: false)]
    private string $date_demande;

    #[ORM\Column(nullable: false)]
    private string $statut;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "employe_id", referencedColumnName: "user_id")]
    private ?Employe $employe = null;

    #[ORM\Column(nullable: true)]
    private ?string $etape_workflow = null;

    #[ORM\Column(nullable: true)]
    private ?string $date_derniere_etape = null;

    #[ORM\Column(nullable: true)]
    private ?string $priorite = null;

    #[ORM\Column(nullable: true)]
    private ?string $deadline_reponse = null;

    #[ORM\Column(nullable: true)]
    private ?string $sla_depasse = null;

    #[ORM\Column(nullable: true)]
    private ?string $pdf_path = null;

    #[ORM\ManyToOne(inversedBy: 'demandeServices')]
    #[ORM\JoinColumn(name: "type_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private TypeService $type;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDateDemande(): ?string
    {
        return $this->date_demande;
    }

    public function setDateDemande(string $date_demande): static
    {
        $this->date_demande = $date_demande;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getEtapeWorkflow(): ?string
    {
        return $this->etape_workflow;
    }

    public function setEtapeWorkflow(?string $etape_workflow): static
    {
        $this->etape_workflow = $etape_workflow;

        return $this;
    }

    public function getDateDerniereEtape(): ?string
    {
        return $this->date_derniere_etape;
    }

    public function setDateDerniereEtape(?string $date_derniere_etape): static
    {
        $this->date_derniere_etape = $date_derniere_etape;

        return $this;
    }

    public function getPriorite(): ?string
    {
        return $this->priorite;
    }

    public function setPriorite(?string $priorite): static
    {
        $this->priorite = $priorite;

        return $this;
    }

    public function getDeadlineReponse(): ?string
    {
        return $this->deadline_reponse;
    }

    public function setDeadlineReponse(?string $deadline_reponse): static
    {
        $this->deadline_reponse = $deadline_reponse;

        return $this;
    }

    public function getSlaDepasse(): ?string
    {
        return $this->sla_depasse;
    }

    public function setSlaDepasse(?string $sla_depasse): static
    {
        $this->sla_depasse = $sla_depasse;

        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdf_path;
    }

    public function setPdfPath(?string $pdf_path): static
    {
        $this->pdf_path = $pdf_path;

        return $this;
    }

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): static
    {
        $this->employe = $employe;

        return $this;
    }

    public function getType(): TypeService
    {
        return $this->type;
    }

    public function setType(TypeService $type): static
    {
        $this->type = $type;

        return $this;
    }

}
