
// Calendar language file
// Lanuage: Polish
// Author: Dariusz Pietrzak, <eyck@ghost.anime.pl>
// Author: Janusz Piwowarski, <jpiw@go2.pl>
// Encoding: UTF-8
// Modified by: The Cacti Group
// Distributed under the same terms as the calendar itself.

// full day names
Calendar._DN 	= new Array("Niedziela",
							"Poniedziałek",
							"Wtorek",
							"Środa",
							"Czwartek",
							"Piątek",
							"Sobota",
							"Niedziela");

// short day names
Calendar._SDN 	= new Array("Nie",
							"Pn",
							"Wt",
							"Śr",
							"Cz",
							"Pt",
							"So",
							"Nie");

// full month names
Calendar._MN 	= new Array("Styczeń",
							"Luty",
							"Marzec",
							"Kwiecień",
							"Maj",
							"Czerwiec",
							"Lipiec",
							"Sierpień",
							"Wrzesień",
							"Październik",
							"Listopad",
							"Grudzień");

// short month names
Calendar._SMN 	= new Array("Sty",
							"Lut",
							"Mar",
							"Kwi",
							"Maj",
							"Cze",
							"Lip",
							"Sie",
							"Wrz",
							"Paź",
							"Lis",
							"Gru");

// First day of the week. "0" means display Sunday first, "1" means display Monday first
Calendar._FD = 0;

// Tooltips, About page and date format
Calendar._TT 					= {};
Calendar._TT["INFO"] 			= "O kalendarzu";
Calendar._TT["PREV_YEAR"] 		= "Poprzedni rok (przytrzymaj dla menu)";
Calendar._TT["PREV_MONTH"] 		= "Poprzedni miesiąc (przytrzymaj dla menu)";
Calendar._TT["GO_TODAY"] 		= "Idź do dzisiaj";
Calendar._TT["NEXT_MONTH"] 		= "Następny miesiąc (przytrzymaj dla menu)";
Calendar._TT["NEXT_YEAR"] 		= "Następny rok (przytrzymaj dla menu)";
Calendar._TT["SEL_DATE"] 		= "Wybierz datę";
Calendar._TT["DRAG_TO_MOVE"] 	= "Przeciągnij by przesunąć";
Calendar._TT["PART_TODAY"] 		= " (dzisiaj)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] 		= "Display %s first";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] 		= "0,6";

Calendar._TT["CLOSE"] 			= "Zamknij";
Calendar._TT["TODAY"] 			= "Dzisiaj";
Calendar._TT["TIME_PART"] 		= "(Shift-)Kliknij lub przeciągnij by zmienić wartość";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
Calendar._TT["TT_DATE_FORMAT"] 	= "%e %B, %A";

Calendar._TT["WK"] 				= "ty";
Calendar._TT["TIME"] 			= "Time:";


Calendar._TT["ABOUT"] 			=
	"DHTML Date/Time Selector\n" +
	"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
	"Aby pobrać najnowszą wersję, odwiedź: http://www.dynarch.com/projects/calendar/\n" +
	"Dostępny na licencji GNU LGPL. Zobacz szczegóły na http://gnu.org/licenses/lgpl.html." +
	"\n\n" +
	"Wybór daty:\n" +
	"- Użyj przycisków \xab, \xbb by wybrać rok\n" +
	"- Użyj przycisków " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " by wybrać miesiąc\n" +
	"- Przytrzymaj klawisz myszy nad jednym z powyższych przycisków dla szybszego wyboru.";

Calendar._TT["ABOUT_TIME"] =
	"\n\n" +
	"Wybór czasu:\n" +
	"- Kliknij na jednym z pól czasu by zwiększyć jego wartość\n" +
	"- lub kliknij trzymając Shift by zmiejszyć jego wartość\n" +
	"- lub kliknij i przeciągnij dla szybszego wyboru.";