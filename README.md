# BESCHREIBUNG
Bei diesem Plugin geht es um eine NPC Datenbank für Mybb Foren. In diesen ist es möglich NPCs auf einer extra Seite zu hinterlegen. Man kann einstellen, wer die NPCs erstellen, löschen und auch bearbeiten kann. 
Es können Altersspannen, Geschlechter & Kategorien in den Einstellungen hinterlegt werden, über welche User innerhalb der DB filtern können. 

# VORAUSSETZUNG
Man braucht einen NPC Account, dieser ist in den Einstellungen zu hinterlegen. 

### Einstellungen wegen Gästen:
- Können Gäste die NPC Datenbank generell sehen?
- Wenn ja, können sie Bilder sehen?
- Wenn nein, dann kann man auch ein No-Image angeben

### Beim Erstellen eines neuen NPCs:
- Altersspanne (aus Einstellungen) auswählbar
- Kategorie (aus Einstellungen) auswählbar
- Geschlecht (aus Einstellungen) auswählbar
- Beschreibung
- Avatarbild (diese werden in einem neuen Ordner auf dem Server gespeichert)

### IN DER POSTBIT 
- Post mit einem NPC Account (in Einstellungen hinterlegbar) und über ein Dropdown Auswahl der hinterlegten NPCs möglich
- Im Post wird nach der Auswahl eines NPCs dann angezeigt mit welchem gepostet wurde, das eventuell hinterlegte Bild und wenn man will noch die Beschreibung des NPC´s (optional)

---

## Neue Templates (in Global):
- npcdb_bit	
- npcdb_confirm_delete	
- npcdb_edit	
- npcdb_filter
- npcdb_filternpc
- npcdb_guest
- npcdb_main
- npcdb_new
- npcdb_postbit

## Neuer Ordner
- npcdb unter uploades am Hauptverzeichnis

## Neue Variablen:
- postbit/postbit_classic `{$post['npc_postbit']}` vor `{$post['message']}`
- newreply `{$filternpc}` hinter `{$loginbox}`
- newthread `{$filternpc}` hinter `{$loginbox}`
- editpost  `{$filternpc}` vor `{$posticons}`
- npcdb_postbit  `{$post['npcdesc']}` für Beschreibung innerhalb der Postbit
