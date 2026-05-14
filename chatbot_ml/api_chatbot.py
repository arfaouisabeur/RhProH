import pickle
import unicodedata
import re
import random
from flask import Flask, request, jsonify
from flask_cors import CORS
from datetime import datetime

app = Flask(__name__)
CORS(app)

with open('modele_chatbot_rhpro.pkl', 'rb') as f:
    MODELE = pickle.load(f)

print("Modèle chargé avec succès !")

# ════════════════════════════════════════════════════════
#  RÉPONSES MULTILINGUES  (fr / en / ar)
# ════════════════════════════════════════════════════════
REPONSES = {
    'fr': {
        "taches_en_cours":       "🔄 {taches_en_cours} tâche(s) en cours sur {taches_total}. ({taches_urgentes} urgentes, {taches_retard} en retard)",
        "taches_terminees":      "✅ {taches_terminees} tâche(s) terminées sur {taches_total}. Taux : {progression}%.",
        "taches_bloquees":       "🔴 {taches_bloquees} tâche(s) bloquées nécessitent une intervention urgente.",
        "taches_urgentes":       "⚡ {taches_urgentes} tâche(s) urgentes ou haute priorité.",
        "taches_retard":         "⏰ {taches_retard} tâche(s) dépassent leur deadline.",
        "taches_total":          "📊 {taches_total} tâches : {taches_en_cours}🔄 en cours | {taches_terminees}✅ terminées | {taches_bloquees}🔴 bloquées | {taches_retard}⏰ en retard",
        "taches_basse_priorite": "🟢 {taches_basse_priorite} tâche(s) de basse priorité.",
        "taches_par_employe":    "👥 {employe_surcharge} a le plus de tâches ({nb_taches_surcharge}). {employe_disponible} en a le moins ({nb_taches_dispo}).",
        "taches_non_assignees":  "⚠️ {taches_non_assignees} tâche(s) sans responsable assigné.",
        "projets_actifs":        "🚀 {projets_actifs} projet(s) actifs. Avancement moyen : {avancement_moyen}%. {projets_retard} en retard.",
        "projets_termines":      "🏁 {projets_termines} projet(s) terminés avec succès.",
        "projets_retard":        "🚨 {projets_retard} projet(s) risquent un retard.",
        "meilleur_projet":       "🏆 Meilleur projet : « {meilleur_projet} » à {meilleur_avancement}%.",
        "progression_globale":   "📈 Avancement global : {progression}% — {taches_terminees}/{taches_total} tâches.",
        "projets_sans_taches":   "📁 {projets_sans_taches} projet(s) sans aucune tâche assignée.",
        "employe_surcharge":     "😓 Employé le plus chargé : {employe_surcharge} ({nb_taches_surcharge} tâches).",
        "employe_disponible":    "😊 Employé le plus disponible : {employe_disponible} ({nb_taches_dispo} tâche(s)).",
        "performance_employes":  "🥇 Meilleure performance : {meilleur_employe} avec {taux_completion}%.",
        "charge_equipe":         "⚖️ {nb_employes} employés — {taches_total} tâches — moyenne {charge_moyenne}/employé. Plus chargé : {employe_surcharge}",
        "stats_generales":       "📊 Bilan : {projets_actifs} projets | {taches_total} tâches : {taches_terminees}✅ {taches_en_cours}🔄 {taches_bloquees}🔴 {taches_retard}⏰ | {progression}%",
        "salutation":            "👋 Bonjour ! Je suis l'assistant RHPro. Posez-moi une question sur vos projets, tâches ou votre équipe.",
        "remerciement":          "😊 Avec plaisir ! N'hésitez pas si vous avez d'autres questions.",
        "au_revoir":             "👋 Au revoir ! Bonne journée.",
        "aide":                  "💡 Questions possibles :\n• Tâches bloquées / urgentes / en retard\n• Projets actifs / avancement\n• Employé surchargé / disponible\n• Statistiques générales",
        "incompris":             "🤔 Je n'ai pas compris. Essayez : « tâches bloquées », « projets en retard », « statistiques ».",
        "inapproprie":           "🤝 Restons professionnels ! Je suis ici pour vous aider avec vos projets et tâches.",
    },
    'en': {
        "taches_en_cours":       "🔄 {taches_en_cours} task(s) in progress out of {taches_total}. ({taches_urgentes} urgent, {taches_retard} late)",
        "taches_terminees":      "✅ {taches_terminees} task(s) completed out of {taches_total}. Rate: {progression}%.",
        "taches_bloquees":       "🔴 {taches_bloquees} task(s) are blocked and need urgent attention.",
        "taches_urgentes":       "⚡ {taches_urgentes} urgent or high-priority task(s).",
        "taches_retard":         "⏰ {taches_retard} task(s) have passed their deadline.",
        "taches_total":          "📊 {taches_total} tasks: {taches_en_cours}🔄 in progress | {taches_terminees}✅ done | {taches_bloquees}🔴 blocked | {taches_retard}⏰ late",
        "taches_basse_priorite": "🟢 {taches_basse_priorite} low-priority task(s).",
        "taches_par_employe":    "👥 {employe_surcharge} has the most tasks ({nb_taches_surcharge}). {employe_disponible} has the least ({nb_taches_dispo}).",
        "taches_non_assignees":  "⚠️ {taches_non_assignees} task(s) have no assigned employee.",
        "projets_actifs":        "🚀 {projets_actifs} active project(s). Average progress: {avancement_moyen}%. {projets_retard} late.",
        "projets_termines":      "🏁 {projets_termines} project(s) successfully completed.",
        "projets_retard":        "🚨 {projets_retard} project(s) are at risk of delay.",
        "meilleur_projet":       "🏆 Best project: « {meilleur_projet} » at {meilleur_avancement}%.",
        "progression_globale":   "📈 Global progress: {progression}% — {taches_terminees}/{taches_total} tasks.",
        "projets_sans_taches":   "📁 {projets_sans_taches} project(s) have no tasks assigned.",
        "employe_surcharge":     "😓 Most loaded employee: {employe_surcharge} ({nb_taches_surcharge} tasks).",
        "employe_disponible":    "😊 Most available employee: {employe_disponible} ({nb_taches_dispo} task(s)).",
        "performance_employes":  "🥇 Best performance: {meilleur_employe} with {taux_completion}%.",
        "charge_equipe":         "⚖️ {nb_employes} employees — {taches_total} tasks — avg {charge_moyenne}/employee. Most loaded: {employe_surcharge}",
        "stats_generales":       "📊 Summary: {projets_actifs} projects | {taches_total} tasks: {taches_terminees}✅ {taches_en_cours}🔄 {taches_bloquees}🔴 {taches_retard}⏰ | {progression}%",
        "salutation":            "👋 Hello! I'm the RHPro assistant. Ask me about your projects, tasks or team.",
        "remerciement":          "😊 You're welcome! Feel free to ask more questions.",
        "au_revoir":             "👋 Goodbye! Have a great day.",
        "aide":                  "💡 You can ask about:\n• Blocked / urgent / late tasks\n• Active projects / progress\n• Overloaded / available employee\n• General statistics",
        "incompris":             "🤔 I didn't understand. Try: 'blocked tasks', 'late projects', 'statistics'.",
        "inapproprie":           "🤝 Let's keep it professional! I'm here to help with your projects and tasks.",
    },
    'ar': {
        "taches_en_cours":       "🔄 {taches_en_cours} مهمة قيد التنفيذ من أصل {taches_total}. ({taches_urgentes} عاجلة، {taches_retard} متأخرة)",
        "taches_terminees":      "✅ {taches_terminees} مهمة مكتملة من أصل {taches_total}. النسبة: {progression}%.",
        "taches_bloquees":       "🔴 {taches_bloquees} مهمة محجوبة تحتاج تدخلاً عاجلاً.",
        "taches_urgentes":       "⚡ {taches_urgentes} مهمة عاجلة أو ذات أولوية عالية.",
        "taches_retard":         "⏰ {taches_retard} مهمة تجاوزت موعدها النهائي.",
        "taches_total":          "📊 {taches_total} مهمة: {taches_en_cours}🔄 جارية | {taches_terminees}✅ مكتملة | {taches_bloquees}🔴 محجوبة | {taches_retard}⏰ متأخرة",
        "taches_basse_priorite": "🟢 {taches_basse_priorite} مهمة ذات أولوية منخفضة.",
        "taches_par_employe":    "👥 {employe_surcharge} لديه أكثر المهام ({nb_taches_surcharge}). {employe_disponible} لديه أقلها ({nb_taches_dispo}).",
        "taches_non_assignees":  "⚠️ {taches_non_assignees} مهمة بدون موظف مسؤول.",
        "projets_actifs":        "🚀 {projets_actifs} مشروع نشط. متوسط التقدم: {avancement_moyen}%. {projets_retard} متأخرة.",
        "projets_termines":      "🏁 {projets_termines} مشروع مكتمل بنجاح.",
        "projets_retard":        "🚨 {projets_retard} مشروع معرض لخطر التأخير.",
        "meilleur_projet":       "🏆 أفضل مشروع: « {meilleur_projet} » بنسبة {meilleur_avancement}%.",
        "progression_globale":   "📈 التقدم العام: {progression}% — {taches_terminees}/{taches_total} مهمة.",
        "projets_sans_taches":   "📁 {projets_sans_taches} مشروع بدون مهام.",
        "employe_surcharge":     "😓 الموظف الأكثر تحميلاً: {employe_surcharge} ({nb_taches_surcharge} مهمة).",
        "employe_disponible":    "😊 الموظف الأكثر توفراً: {employe_disponible} ({nb_taches_dispo} مهمة).",
        "performance_employes":  "🥇 أفضل أداء: {meilleur_employe} بنسبة {taux_completion}%.",
        "charge_equipe":         "⚖️ {nb_employes} موظف — {taches_total} مهمة — معدل {charge_moyenne}/موظف. الأكثر تحميلاً: {employe_surcharge}",
        "stats_generales":       "📊 ملخص: {projets_actifs} مشروع | {taches_total} مهمة: {taches_terminees}✅ {taches_en_cours}🔄 {taches_bloquees}🔴 {taches_retard}⏰ | {progression}%",
        "salutation":            "👋 مرحباً! أنا مساعد RHPro. اسألني عن مشاريعك أو مهامك أو فريقك.",
        "remerciement":          "😊 بكل سرور! لا تتردد في طرح المزيد من الأسئلة.",
        "au_revoir":             "👋 وداعاً! أتمنى لك يوماً سعيداً.",
        "aide":                  "💡 يمكنك السؤال عن:\n• المهام المحجوبة / العاجلة / المتأخرة\n• المشاريع النشطة / التقدم\n• الموظف المثقل / المتاح\n• الإحصائيات العامة",
        "incompris":             "🤔 لم أفهم سؤالك. جرب: 'المهام المحجوبة'، 'المشاريع المتأخرة'، 'الإحصائيات'.",
        "inapproprie":           "🤝 لنبقَ محترفين! أنا هنا لمساعدتك في إدارة مشاريعك ومهامك.",
    },
}

CONTEXTE_DEFAUT = {
    'taches_en_cours': 0, 'taches_terminees': 0, 'taches_bloquees': 0,
    'taches_urgentes': 0, 'taches_retard': 0, 'taches_total': 0,
    'taches_basse_priorite': 0, 'taches_non_assignees': 0,
    'projets_actifs': 0, 'projets_termines': 0, 'projets_retard': 0,
    'projets_sans_taches': 0, 'avancement_moyen': 0,
    'meilleur_projet': 'Aucun', 'meilleur_avancement': 0,
    'employe_surcharge': 'Aucun', 'nb_taches_surcharge': 0,
    'employe_disponible': 'Aucun', 'nb_taches_dispo': 0,
    'meilleur_employe': 'Aucun', 'taux_completion': 0,
    'progression': 0, 'nb_employes': 0, 'charge_moyenne': 0,
}

# ════════════════════════════════════════════════════════
#  SYSTÈME DE DÉTECTION INTELLIGENT PAR MOTS-CLÉS
# ════════════════════════════════════════════════════════

def normaliser(texte):
    """Supprime accents, ponctuation, met en minuscules."""
    texte = texte.lower().strip()
    texte = ''.join(
        c for c in unicodedata.normalize('NFD', texte)
        if unicodedata.category(c) != 'Mn'
    )
    texte = re.sub(r"[^\w\s]", ' ', texte)
    texte = re.sub(r'\s+', ' ', texte).strip()
    return texte

def levenshtein(a, b):
    """Distance d'édition entre deux chaînes."""
    if len(a) < len(b):
        return levenshtein(b, a)
    if len(b) == 0:
        return len(a)
    prev = list(range(len(b) + 1))
    for i, ca in enumerate(a):
        curr = [i + 1]
        for j, cb in enumerate(b):
            curr.append(min(prev[j + 1] + 1, curr[j] + 1, prev[j] + (ca != cb)))
        prev = curr
    return prev[len(b)]

def similarite_mot(mot1, mot2):
    """Score 0-1 basé sur Levenshtein normalisé."""
    dist = levenshtein(mot1, mot2)
    max_len = max(len(mot1), len(mot2), 1)
    return 1.0 - dist / max_len

def mots_similaires(mot, vocabulaire, seuil=0.82):
    """Vérifie si un mot est proche d'un mot du vocabulaire."""
    for v in vocabulaire:
        if similarite_mot(mot, v) >= seuil:
            return True
    return False

# Dictionnaire sémantique : synonymes et variantes par concept
SEMANTIQUE = {
    # Concepts tâches
    'tache':      ['tache', 'taches', 'task', 'tasks', 'activite', 'activites', 'mission', 'missions', 'travail',
                   'مهمة', 'مهام', 'نشاط', 'عمل'],
    'projet':     ['projet', 'projets', 'project', 'projects', 'chantier', 'chantiers',
                   'مشروع', 'مشاريع'],
    'employe':    ['employe', 'employes', 'personne', 'personnes', 'membre', 'membres', 'collaborateur', 'equipe', 'staff',
                   'employee', 'employees', 'team', 'member', 'person', 'responsable', 'responsables',
                   'موظف', 'موظفين', 'فريق', 'شخص', 'مسؤول'],

    # Statuts
    'bloque':     ['bloque', 'bloques', 'bloquee', 'bloquees', 'bloquant', 'blocage', 'suspendu', 'stoppe', 'arrete',
                   'blocked', 'blocking', 'stuck', 'suspended',
                   'محجوب', 'محجوبة', 'موقوف', 'متوقف'],
    'urgent':     ['urgent', 'urgents', 'urgente', 'urgentes', 'critique', 'critiques', 'prioritaire', 'prioritaires', 'important', 'haute',
                   'urgent', 'critical', 'high priority', 'important',
                   'عاجل', 'عاجلة', 'حرج', 'أولوية'],
    'retard':     ['retard', 'retards', 'retarde', 'retardes', 'depasse', 'depasses', 'hors delai', 'deadline', 'late', 'overdue', 'danger', 'risque', 'retart'],
    'termine':    ['termine', 'termines', 'terminee', 'terminees', 'fini', 'finis', 'finie', 'finies', 'complete', 'completes', 'done', 'acheve', 'cloture',
                   'completed', 'finished', 'closed', 'done',
                   'مكتمل', 'مكتملة', 'منتهي', 'منجز'],
    'en_cours':   ['en cours', 'encours', 'actif', 'actifs', 'active', 'actives', 'ouvert', 'ouverts', 'running', 'ongoing', 'jeu', 'cours',
                   'in progress', 'active', 'open', 'current',
                   'جارية', 'قيد التنفيذ', 'نشط', 'مفتوح'],
    'basse':      ['basse', 'bas', 'faible', 'faibles', 'peu urgent', 'non urgent', 'low',
                   'low priority', 'minor',
                   'منخفض', 'أولوية منخفضة'],

    # Quantité
    'combien':    ['combien', 'nombre', 'total', 'count', 'quantite', 'volume', 'nb', 'nbre', 'liste', 'affiche', 'montre', 'donne',
                   'how many', 'how much', 'list', 'show', 'count', 'number',
                   'كم', 'عدد', 'قائمة', 'اظهر'],

    # Qualité/performance
    'meilleur':   ['meilleur', 'meilleurs', 'meilleure', 'meilleures', 'top', 'best', 'performant', 'performants',
                   'best', 'top', 'leading',
                   'أفضل', 'الأحسن'],
    'surcharge':  ['surcharge', 'surcharges', 'surchargee', 'surchargees', 'trop', 'beaucoup', 'max', 'maximum', 'plus',
                   'overloaded', 'too much', 'most',
                   'مثقل', 'محمل', 'أكثر'],
    'disponible': ['disponible', 'disponibles', 'libre', 'libres', 'dispo', 'moins', 'minimum', 'min', 'prendre', 'nouvelle',
                   'available', 'free', 'least',
                   'متاح', 'متوفر', 'حر'],

    # Actions/états
    'avancement': ['avancement', 'avancements', 'progression', 'progressions', 'progres', 'avance', 'avances', 'etat', 'etats', 'bilan', 'bilans', 'rapport', 'rapports', 'statut', 'statuts', 'lavancment', 'avancment', 'lavancement', 'avanc',
                   'progress', 'advancement', 'status', 'state', 'report',
                   'تقدم', 'تطور', 'حالة', 'تقرير'],
    'assignation':['assigne', 'assignes', 'assignee', 'assignees', 'attribue', 'attribues', 'responsable', 'responsables', 'orphelin', 'orphelins', 'sans', 'vide', 'vides', 'aucune',
                   'assigned', 'unassigned', 'responsible', 'owner',
                   'مسند', 'غير مسند', 'مسؤول'],
    'charge':     ['charge', 'charges', 'chargee', 'chargees', 'workload', 'repartition', 'distribution', 'equilibre',
                   'workload', 'load', 'distribution',
                   'عبء', 'حمل', 'توزيع'],
    'statistique':['statistique', 'statistiques', 'stats', 'stat', 'resume', 'resumes', 'synthese', 'global', 'globale', 'general', 'generale', 'vue', 'ensemble',
                   'statistics', 'stats', 'summary', 'overview', 'global',
                   'إحصائيات', 'ملخص', 'عام', 'شامل'],
    'performance':['performance', 'performances', 'productivite', 'rendement', 'efficacite', 'taux', 'completion',
                   'performance', 'productivity', 'efficiency', 'rate',
                   'أداء', 'إنتاجية', 'كفاءة'],
}

# Règles de détection : liste de (concepts_requis, concepts_exclus, intention)
# Un message matche si TOUS les concepts_requis sont présents
REGLES_SEMANTIQUES = [
    # ── Tâches ──
    (['tache', 'bloque'],                    [],              'taches_bloquees'),
    (['tache', 'urgent'],                    ['projet'],      'taches_urgentes'),
    (['tache', 'retard'],                    ['projet'],      'taches_retard'),
    (['tache', 'termine'],                   ['projet'],      'taches_terminees'),
    (['tache', 'en_cours'],                  ['projet'],      'taches_en_cours'),
    (['tache', 'basse'],                     [],              'taches_basse_priorite'),
    (['tache', 'assignation'],               [],              'taches_non_assignees'),
    (['tache', 'employe'],                   ['surcharge', 'disponible', 'performance'], 'taches_par_employe'),
    (['tache', 'combien'],                   ['projet', 'bloque', 'urgent', 'retard', 'termine', 'en_cours'], 'taches_total'),
    (['tache'],                              ['projet', 'bloque', 'urgent', 'retard', 'termine', 'en_cours', 'assignation'], 'taches_total'),

    # ── Projets ──
    (['projet', 'retard'],                   [],              'projets_retard'),
    (['projet', 'termine'],                  [],              'projets_termines'),
    (['projet', 'meilleur'],                 [],              'meilleur_projet'),
    (['projet', 'avancement'],               [],              'progression_globale'),
    (['avancement', 'projet'],               [],              'progression_globale'),
    (['projet', 'tache', 'assignation'],     [],              'projets_sans_taches'),
    (['projet', 'tache'],                    ['retard', 'termine', 'meilleur', 'avancement', 'assignation'], 'projets_sans_taches'),
    (['projet', 'combien'],                  ['retard', 'termine', 'avancement'], 'projets_actifs'),
    (['projet'],                             ['retard', 'termine', 'meilleur', 'avancement', 'tache'], 'projets_actifs'),

    # ── Employés ──
    (['employe', 'surcharge'],               [],              'employe_surcharge'),
    (['employe', 'disponible'],              [],              'employe_disponible'),
    (['employe', 'performance'],             [],              'performance_employes'),
    (['meilleur', 'employe'],                [],              'performance_employes'),
    (['meilleur', 'assignation'],            [],              'performance_employes'),
    (['employe', 'charge'],                  [],              'charge_equipe'),

    # ── Global ──
    (['avancement'],                         ['tache', 'employe'], 'progression_globale'),
    (['charge'],                             ['tache'],       'charge_equipe'),
    (['statistique'],                        [],              'stats_generales'),
    (['performance'],                        ['tache'],       'performance_employes'),
]

# Salutations et mots simples
SALUTATIONS = {'bonjour', 'salut', 'hello', 'bonsoir', 'hey', 'coucou', 'hi', 'yo',
               'good morning', 'good evening', 'greetings', 'مرحبا', 'السلام', 'اهلا', 'صباح',
               'سلام', 'هلا', 'هاي', 'مرحبً', 'اهلاً', 'يسلمو', 'هلو'}
REMERCIEMENTS = {'merci', 'thanks', 'thank', 'super', 'parfait', 'nickel', 'cool', 'bravo',
                 'thank you', 'شكرا', 'شكراً'}
AU_REVOIRS = {'au revoir', 'aurevoir', 'bye', 'ciao', 'adieu', 'bonne journee', 'goodbye',
              'see you', 'وداعا', 'مع السلامة'}
AIDE_MOTS = {'aide', 'help', 'quoi', 'comment', 'que', 'peux', 'faire', 'fonctionnes',
             'what', 'how', 'مساعدة', 'ماذا', 'كيف'}

MOTS_INAPPROPRIES = {
    'fuck', 'merde', 'putain', 'connard', 'idiot', 'stupide', 'nul',
    'con', 'salaud', 'batard', 'enculer', 'chier', 'casse', 'shut',
    'damn', 'shit', 'ass', 'bitch', 'crap', 'hell', 'wtf', 'omg',
    'fdp', 'tg', 'va te', 'ferme', 'ta gueule', 'imbecile', 'cretin',
}

# ── Détection de langue ──
MOTS_FR = {'tache', 'taches', 'projet', 'projets', 'employe', 'employes', 'combien',
           'bloque', 'urgent', 'retard', 'termine', 'avancement', 'statistique',
           'charge', 'performance', 'bonjour', 'merci', 'salut'}
MOTS_EN = {'task', 'tasks', 'project', 'projects', 'employee', 'employees', 'how many',
           'blocked', 'urgent', 'late', 'done', 'progress', 'statistics', 'workload',
           'performance', 'hello', 'thanks', 'hi', 'overdue', 'completed'}
MOTS_AR = {'مهمة', 'مهام', 'مشروع', 'مشاريع', 'موظف', 'موظفين', 'كم', 'محجوب',
           'عاجل', 'متأخر', 'مكتمل', 'تقدم', 'إحصائيات', 'أداء', 'مرحبا', 'شكرا',
           'سلام', 'هلا', 'هاي', 'اهلا'}

def detecter_langue(message):
    """Détecte la langue du message : fr / en / ar."""
    msg = message.lower()
    # Arabe : présence de caractères arabes
    if re.search(r'[\u0600-\u06FF]', message):
        return 'ar'
    msg_norme = normaliser(msg)
    mots = set(msg_norme.split())
    score_en = len(mots & MOTS_EN)
    score_fr = len(mots & MOTS_FR)
    if score_en > score_fr:
        return 'en'
    return 'fr'  # défaut français

def get_reponse(intention, langue):
    """Retourne la réponse dans la bonne langue."""
    return REPONSES.get(langue, REPONSES['fr']).get(
        intention,
        REPONSES['fr'].get(intention, "Désolé, je ne peux pas répondre à cela.")
    )

REPONSES_INAPPROPRIEES = [
    "🤝 Je suis ici pour vous aider professionnellement. Posez-moi une question sur vos projets ou tâches.",
    "😊 Restons professionnels ! Je peux vous aider avec vos projets, tâches et statistiques RH.",
    "💼 Je suis votre assistant RHPro. Comment puis-je vous aider avec votre gestion de projets ?",
]

def extraire_concepts(message_norme):
    """Extrait les concepts présents dans le message."""
    mots = message_norme.split()
    concepts_trouves = set()

    for concept, synonymes in SEMANTIQUE.items():
        for synonyme in synonymes:
            # Correspondance exacte de sous-chaîne
            if synonyme in message_norme:
                concepts_trouves.add(concept)
                break
            # Correspondance par similarité sur les mots individuels
            for mot in mots:
                if len(mot) >= 4 and mots_similaires(mot, [synonyme], seuil=0.85):
                    concepts_trouves.add(concept)
                    break

    return concepts_trouves

def detecter_intention_intelligente(message):
    """Détection multi-niveaux : salutations → règles sémantiques → ML."""
    msg_norme = normaliser(message)
    mots = set(msg_norme.split())

    # Niveau 1 : salutations / mots simples
    if mots & SALUTATIONS or msg_norme in SALUTATIONS:
        return 'salutation', 1.0
    if mots & REMERCIEMENTS:
        return 'remerciement', 1.0
    for expr in AU_REVOIRS:
        if expr in msg_norme:
            return 'au_revoir', 1.0
    if mots & AIDE_MOTS and len(mots) <= 4:
        return 'aide', 1.0

    # Niveau 2 : extraction de concepts + règles sémantiques
    concepts = extraire_concepts(msg_norme)

    meilleure_intention = None
    meilleur_score = 0

    for requis, exclus, intention in REGLES_SEMANTIQUES:
        # Tous les concepts requis doivent être présents
        if not all(c in concepts for c in requis):
            continue
        # Aucun concept exclu ne doit être présent
        if any(c in concepts for c in exclus):
            continue
        # Score = nombre de concepts requis (plus spécifique = meilleur)
        score = len(requis)
        if score > meilleur_score:
            meilleur_score = score
            meilleure_intention = intention

    if meilleure_intention:
        return meilleure_intention, 0.9

    # Niveau 3 : fallback ML
    try:
        intention_ml = MODELE.predict([message])[0]
        scores = MODELE.decision_function([message])[0]
        confiance = float(max(scores))
        if confiance >= 0.1:
            return intention_ml, confiance
    except Exception:
        pass

    return None, 0.0


# ════════════════════════════════════════════════════════
#  ROUTE PRINCIPALE
# ════════════════════════════════════════════════════════

def construire_barre(pct, longueur=8):
    """Barre de progression visuelle ex: ████░░░░ 50%"""
    rempli = round(pct / 100 * longueur)
    return '█' * rempli + '░' * (longueur - rempli)

def chercher_projet_par_nom(nom_recherche, details_projets):
    """Trouve un projet dans details_projets par similarité de nom."""
    nom_norme = normaliser(nom_recherche)
    meilleur = None
    meilleur_score = 0
    for p in details_projets:
        nom_p = normaliser(p['nom'])
        # Correspondance exacte
        if nom_norme == nom_p:
            return p
        # Sous-chaîne
        if nom_norme in nom_p or nom_p in nom_norme:
            score = len(nom_norme) / max(len(nom_p), 1)
            if score > meilleur_score:
                meilleur_score = score
                meilleur = p
        # Similarité mot par mot
        mots_recherche = set(nom_norme.split())
        mots_projet = set(nom_p.split())
        communs = mots_recherche & mots_projet
        if communs:
            score = len(communs) / max(len(mots_recherche), len(mots_projet))
            if score > meilleur_score:
                meilleur_score = score
                meilleur = p
        # Levenshtein sur le nom complet
        sim = similarite_mot(nom_norme, nom_p)
        if sim > meilleur_score and sim > 0.6:
            meilleur_score = sim
            meilleur = p
    return meilleur if meilleur_score > 0.4 else None

def extraire_nom_projet(message_norme, details_projets):
    """Extrait le nom d'un projet mentionné dans le message."""
    # Mots à ignorer (stopwords)
    stopwords = {'combien', 'de', 'des', 'du', 'le', 'la', 'les', 'un', 'une',
                 'pour', 'dans', 'sur', 'avec', 'tache', 'taches', 'projet',
                 'projets', 'avancement', 'progression', 'statut', 'etat',
                 'quelle', 'quel', 'quelles', 'quels', 'est', 'sont', 'il',
                 'y', 'a', 'au', 'aux', 'ce', 'cette', 'mon', 'ma', 'mes',
                 'combien', 'nombre', 'total', 'liste', 'affiche', 'montre'}

    mots = [m for m in message_norme.split() if m not in stopwords and len(m) >= 2]

    if not mots:
        return None

    # Essayer des combinaisons de mots consécutifs (2, 3, 4 mots)
    for taille in range(min(4, len(mots)), 0, -1):
        for i in range(len(mots) - taille + 1):
            candidat = ' '.join(mots[i:i+taille])
            projet = chercher_projet_par_nom(candidat, details_projets)
            if projet:
                return projet

    return None
@app.route('/chatbot', methods=['POST'])
def chatbot():
    data         = request.get_json()
    message      = data.get('message', '').strip()
    contexte_raw = data.get('contexte', {})
    if not isinstance(contexte_raw, dict):
        contexte_raw = {}
    contexte = {**CONTEXTE_DEFAUT, **contexte_raw}

    if not message:
        return jsonify({'erreur': 'Message vide'}), 400

    # ── Filtre messages inappropriés ──
    msg_norme_check = normaliser(message)
    mots_msg = set(msg_norme_check.split())
    if mots_msg & MOTS_INAPPROPRIES or any(m in msg_norme_check for m in MOTS_INAPPROPRIES):
        langue = detecter_langue(message)
        reponse = get_reponse('inapproprie', langue)
        return jsonify({
            'reponse'   : reponse,
            'intention' : 'inapproprie',
            'confiance' : 1.0,
            'langue'    : langue,
            'timestamp' : datetime.now().strftime('%H:%M'),
        })

    # Forcer les valeurs numériques en int pour éviter "20.0%"
    for k, v in contexte.items():
        if isinstance(v, float) and v == int(v):
            contexte[k] = int(v)

    intention, confiance = detecter_intention_intelligente(message)

    # ── Détecter la langue ──
    langue = detecter_langue(message)

    # ── Détection de projet spécifique dans le message ──
    details = contexte.get('details_projets', [])
    if details:
        msg_norme = normaliser(message)
        projet_trouve = extraire_nom_projet(msg_norme, details)

        if projet_trouve:
            p = projet_trouve
            barre = construire_barre(p['avancement'])
            statut_labels = {
                'fr': {'en_cours': '🟢 En cours', 'termine': '✅ Terminé', 'en_attente': '⏸️ En attente'},
                'en': {'en_cours': '🟢 In progress', 'termine': '✅ Completed', 'en_attente': '⏸️ On hold'},
                'ar': {'en_cours': '🟢 جارٍ', 'termine': '✅ مكتمل', 'en_attente': '⏸️ معلق'},
            }
            statut_label = statut_labels.get(langue, statut_labels['fr']).get(p['statut'], p['statut'])

            mots = set(normaliser(message).split())
            veut_avancement = bool(mots & {'avancement', 'progression', 'avance', 'progres', 'pourcent', 'taux', 'etat',
                                           'progress', 'status', 'تقدم', 'حالة'})
            veut_taches     = bool(mots & {'tache', 'taches', 'combien', 'nombre', 'total', 'liste',
                                           'task', 'tasks', 'how many', 'مهمة', 'مهام', 'كم'})

            if langue == 'en':
                if veut_avancement or not veut_taches:
                    reponse = f"📊 Project « {p['nom']} »\n   Status: {statut_label}\n   {barre} {p['avancement']}%\n   📋 {p['total']} tasks: {p['terminees']}✅ done | {p['en_cours']}🔄 active | {p['bloquees']}🔴 blocked"
                else:
                    reponse = f"� Project « {p['nom']} » — {p['total']} task(s)\n   ✅ {p['terminees']} done\n   🔄 {p['en_cours']} active\n   🔴 {p['bloquees']} blocked\n   {barre} {p['avancement']}%"
            elif langue == 'ar':
                if veut_avancement or not veut_taches:
                    reponse = f"📊 مشروع « {p['nom']} »\n   الحالة: {statut_label}\n   {barre} {p['avancement']}%\n   📋 {p['total']} مهمة: {p['terminees']}✅ مكتملة | {p['en_cours']}🔄 جارية | {p['bloquees']}🔴 محجوبة"
                else:
                    reponse = f"📋 مشروع « {p['nom']} » — {p['total']} مهمة\n   ✅ {p['terminees']} مكتملة\n   🔄 {p['en_cours']} جارية\n   🔴 {p['bloquees']} محجوبة\n   {barre} {p['avancement']}%"
            else:
                if veut_avancement or not veut_taches:
                    reponse = f"📊 Projet « {p['nom']} »\n   Statut : {statut_label}\n   {barre} {p['avancement']}%\n   📋 {p['total']} tâches : {p['terminees']}✅ terminées | {p['en_cours']}🔄 actives | {p['bloquees']}🔴 bloquées"
                else:
                    reponse = f"📋 Projet « {p['nom']} » — {p['total']} tâche(s)\n   ✅ {p['terminees']} terminée(s)\n   🔄 {p['en_cours']} active(s)\n   🔴 {p['bloquees']} bloquée(s)\n   {barre} {p['avancement']}%"

            return jsonify({
                'reponse'   : reponse,
                'intention' : 'projet_specifique',
                'confiance' : 1.0,
                'timestamp' : datetime.now().strftime('%H:%M'),
            })

    if not intention:
        reponse = get_reponse('incompris', langue)
        return jsonify({
            'reponse': reponse, 'intention': 'inconnu',
            'confiance': 0, 'langue': langue,
            'timestamp': datetime.now().strftime('%H:%M')
        })

    template = get_reponse(intention, langue)

    # Réponse spéciale détaillée pour l'avancement des projets
    if intention == 'progression_globale':
        details = contexte.get('details_projets', [])
        if details:
            labels = {
                'fr': {'titre': '📈 Avancement global', 'taches': 'tâches'},
                'en': {'titre': '📈 Global progress',   'taches': 'tasks'},
                'ar': {'titre': '📈 التقدم العام',       'taches': 'مهمة'},
            }.get(langue, {'titre': '📈 Avancement global', 'taches': 'tâches'})

            lignes = [f"{labels['titre']} : {contexte.get('progression', 0)}% ({contexte.get('taches_terminees', 0)}/{contexte.get('taches_total', 0)} {labels['taches']})\n"]
            for p in details:
                barre = construire_barre(p['avancement'])
                statut_icon = '🟢' if p['statut'] == 'en_cours' else ('✅' if p['statut'] == 'termine' else '⏸️')
                lignes.append(
                    f"{statut_icon} {p['nom']}\n"
                    f"   {barre} {p['avancement']}%\n"
                    f"   📋 {p['total']} : {p['terminees']}✅ {p['en_cours']}🔄 {p['bloquees']}🔴"
                )
            reponse = "\n".join(lignes)
        else:
            try:
                reponse = template.format(**contexte)
            except KeyError:
                reponse = template
    else:
        try:
            reponse = template.format(**contexte)
        except KeyError:
            reponse = template

    return jsonify({
        'reponse'   : reponse,
        'intention' : intention,
        'confiance' : round(confiance, 2),
        'langue'    : langue,
        'timestamp' : datetime.now().strftime('%H:%M'),
    })

@app.route('/sante', methods=['GET'])
def sante():
    return jsonify({'statut': 'ok', 'modele': 'RHPro ML + Semantic'})

if __name__ == '__main__':
    print("API Flask démarrée sur port 5001 — Système sémantique intelligent activé")
    app.run(host='0.0.0.0', port=5001, debug=False)
