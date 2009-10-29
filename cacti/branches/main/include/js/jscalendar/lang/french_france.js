
// Calendar language file
// Lanuage: French
// Author: David Duret, <pilgrim@mala-template.net>
// Encoding: UTF-8
// Modified by: The Cacti Group
// Distributed under the same terms as the calendar itself.

// full day names
Calendar._DN 	= new Array("Dimanche",
							"Lundi",
							"Mardi",
							"Mercredi",
							"Jeudi",
							"Vendredi",
							"Samedi",
							"Dimanche");

// short day names
Calendar._SDN 	= new Array("Dim",
							"Lun",
							"Mar",
							"Mar",
							"Jeu",
							"Ven",
							"Sam",
							"Dim");

// full month names
Calendar._MN 	= new Array("Janvier",
							"Février",
							"Mars",
							"Avril",
							"Mai",
							"Juin",
							"Juillet",
							"Août",
							"Septembre",
							"Octobre",
							"Novembre",
							"Décembre");

// short month names
Calendar._SMN 	= new Array("Jan",
							"Fev",
							"Mar",
							"Avr",
							"Mai",
							"Juin",
							"Juil",
							"Aout",
							"Sep",
							"Oct",
							"Nov",
							"Dec");

// First day of the week. "0" means display Sunday first, "1" means display Monday first
Calendar._FD = 0;

// Tooltips, About page and date format
Calendar._TT 					= {};
Calendar._TT["PREV_YEAR"] 		= "Année préc. (maintenir pour menu)";
Calendar._TT["PREV_MONTH"] 		= "Mois préc. (maintenir pour menu)";
Calendar._TT["GO_TODAY"] 		= "Atteindre la date du jour";
Calendar._TT["NEXT_MONTH"] 		= "Mois suiv. (maintenir pour menu)";
Calendar._TT["NEXT_YEAR"] 		= "Année suiv. (maintenir pour menu)";
Calendar._TT["SEL_DATE"] 		= "Sélectionner une date";
Calendar._TT["DRAG_TO_MOVE"] 	= "Déplacer";
Calendar._TT["PART_TODAY"] 		= " (Aujourd'hui)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] 		= "Afficher %s en premier";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] 		= "0,6";

Calendar._TT["CLOSE"] 			= "Fermer";
Calendar._TT["TODAY"] 			= "Aujourd'hui";
Calendar._TT["TIME_PART"] 		= "(Maj-)Clic ou glisser pour modifier la valeur";

// date formats
Calendar._TT["DEF_DATE_FORMAT"]	= "%d/%m/%Y";
Calendar._TT["TT_DATE_FORMAT"]	= "%a, %b %e";

Calendar._TT["WK"] 				= "Sem.";
Calendar._TT["TIME"] 			= "Heure :";


Calendar._TT["ABOUT"] 			=
	"DHTML Date/Time Selector\n" +							// Do not translate this this
	"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + 	// Do not translate this this
	"Pour la derniere version visitez : http://www.dynarch.com/projects/calendar/\n" +
	"Distribué par GNU LGPL.  Voir http://gnu.org/licenses/lgpl.html pour les details." +
	"\n\n" +
	"Selection de la date :\n" +
	"- Utiliser les bouttons \xab, \xbb  pour selectionner l\'annee\n" +
	"- Utiliser les bouttons " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " pour selectionner les mois\n" +
	"- Garder la souris sur n'importe quels boutons pour une selection plus rapide";

Calendar._TT["ABOUT_TIME"] 		= 
	"\n\n" +
	"Selection de l\'heure :\n" +
	"- Cliquer sur heures ou minutes pour incrementer\n" +
	"- ou Maj-clic pour decrementer\n" +
	"- ou clic et glisser-deplacer pour une selection plus rapide";