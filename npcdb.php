<?php
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'npcdb.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "./global.php";

global $db, $cache, $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer, $add_npc, $parser, $kat_select, $age_options, $kate_options, $gender_options;

	// PARSER - HTML und CO erlauben
	require_once MYBB_ROOT . "inc/class_parser.php";
	$parser = new postParser;
	$parser_options = array(
		"allow_html" => 1,
		"allow_mycode" => 1,
		"allow_smilies" => 1,
		"allow_imgcode" => 1,
		"filter_badwords" => 0,
		"nl2br" => 1,
		"allow_videocode" => 0
	);

	// Sprachdatei laden
	$lang->load('npcdb');

	// Einstellungen ziehen
	$settingguest = $mybb->settings['npcdb_guest'];

	//Kategorien sind durch Komma getrennt
	$settingkats = $mybb->settings['npcdb_kats'];
	$kategorien = explode(",", $settingkats);

	//Altersspanne sind durch Komma getrennt
	$settingage = $mybb->settings['npcdb_age'];
	$ages = explode(",", $settingage);
	
	//Geschlecht sind durch Komma getrennt
	$settinggender = $mybb->settings['npcdb_gender'];
	$genders = explode(",", $settinggender);

	//Einstellungen für die Filter
	$settingfilter = $mybb->settings['npcdb_filter'];
	$settingcreate = $mybb->settings['npcdb_create'];
	$settingdelete = $mybb->settings['npcdb_delete'];
	$settingedit = $mybb->settings['npcdb_edit'];

	// Einstellungen für die Bilder holen
	$settingimage = $mybb->settings['npcdb_image'];
	$settingimgheight = $mybb->settings['npcdb_image_height'];
	$settingimgwidth = $mybb->settings['npcdb_image_weight'];
	$settingimgguest = $mybb->settings['npcdb_image_guest'];
	$settingimgguest_nopic = $mybb->settings['npcdb_image_guest_guest'];
	$settingimgguest_pic = $mybb->settings['npcdb_image_guest_noava'];

	// HAUPTSEITE 
	if ($mybb->input['action'] != "npcdb") {

		// Navigation bauen
		add_breadcrumb("NPC Datenbank", "npcdb.php");

		// Wenn Gäste nicht sehen dürfen UND keine Benutzer sind
		if ($settingguest == 0 AND $mybb->user['uid'] == 0) {
			eval('$page = "' . $templates->get('npcdb_guest') . '";');
			output_page($page);
		} else {         

			// NPC Hinzufügen - speichern
			if ($mybb->get_input('action') == "save_npc") {
				$new_entry = [
					"npcid" => (int)$mybb->get_input('npcid'),
					"npccreater" => (int)$mybb->user['uid'],
					"npcname" => $db->escape_string($mybb->get_input('npcname')),
					"npcdesc" => $db->escape_string($mybb->get_input('npcdesc')),
					"npcage" => $db->escape_string($mybb->get_input('age')),
					"npckat" => $db->escape_string($mybb->get_input('kat')),
					"npcgender" => $db->escape_string($mybb->get_input('gender')),
				];

				// Bild-Upload verarbeiten
				if (isset($_FILES['npcimage']) && $_FILES['npcimage']['error'] == UPLOAD_ERR_OK) {
					$upload_dir = 'uploads/npcdb/';
					$file_name = basename($_FILES['npcimage']['name']);
					$target_file = $upload_dir . $file_name;

					// Überprüfen, ob das Bild tatsächlich ein Bild ist
					$check = getimagesize($_FILES['npcimage']['tmp_name']);
					if ($check !== false) {
						if (move_uploaded_file($_FILES['npcimage']['tmp_name'], $target_file)) {
							$new_entry['npcimage'] = $db->escape_string($file_name);
						} else {
							echo "Fehler beim Hochladen des Bildes.";
						}
					} else {
						echo "Die Datei ist kein Bild.";
					}
				}

				$db->insert_query("npcdb", $new_entry);
			}

			
			// Überprüfen, ob die Aktion npcdelete angeklickt wurde
        if ($mybb->get_input('action') == "npcdelete") {
            $npcid = (int)$mybb->get_input('npcid');

            // Sicherheitsüberprüfung: Überprüfen des my_post_key
            if ($mybb->input['my_post_key'] == $mybb->post_code) {
                // NPC-Daten abrufen, um sie auf der Bestätigungsseite anzuzeigen
                $npc_query = $db->simple_select("npcdb", "*", "npcid = '{$npcid}'");
                $npc = $db->fetch_array($npc_query);

                // Bestätigungsseite anzeigen
                eval("\$page = \"" . $templates->get("npcdb_confirm_delete") . "\";"); // Template für die Bestätigungsseite
                output_page($page);
                exit; // Beendet das Skript hier
            } else {
                flash_message("Ungültige Anfrage.", 'error');
                header("Location: npcdb.php");
                exit; // Beendet das Skript nach der Weiterleitung
            }
        }


			// Überprüfen, ob die Aktion confirm_delete angeklickt wurde
        	if ($mybb->get_input('action') == "confirm_delete") {
				$npcid = (int)$mybb->get_input('npcid');

				// Sicherheitsüberprüfung: Überprüfen des my_post_key
				if ($mybb->input['my_post_key'] == $mybb->post_code) {
					// NPC aus der Datenbank löschen
					$db->delete_query("npcdb", "npcid = '{$npcid}'");

					// Erfolgreiche Löschmeldung (optional)
					redirect('npcdb.php', 'NPC erfolgreich gelöscht');
					exit; // Beende das Skript nach der Weiterleitung
				} else {
					redirect('npcdb.php', 'ungültige anfrage');
					header("Location: npcdb.php");
					exit; // Beende das Skript nach der Weiterleitung
				}
			}

			
			// Überprüfen, ob die Aktion npcedit angeklickt wurde
			if ($mybb->get_input('action') == "npcedit") {
				$npcid = (int)$mybb->get_input('npcid');

				// NPC-Daten abrufen
				$npc_query = $db->simple_select("npcdb", "*", "npcid = '{$npcid}'");
				$npc = $db->fetch_array($npc_query);

				// Überprüfen, ob NPC existiert
				if (!$npc) {
					redirect('npcdb.php', 'NPC nicht gefunden');
					header("Location: npcdb.php");
					exit;
				}				
				
					//Einstellungen für Dropdown holen bevor das EDIT TPL kommt
					//Kategorien  aus Einstellungen holen - siehe oben		
					$kategorien = array_map('trim', $kategorien); // Entfernt Leerzeichen von den Kategorien

					$kategorien = explode(",", $settingkats); // mit explode werden sie einzeln rausgeholt
					$kategorien = array_map('trim', $kategorien); // Entfernt Leerzeichen von den Kategorien

					//Leeren für PHP 8
					$kat_select ='';
					foreach ($kategorien as $kategorie) {
						$selected = ($kategorie == $npc['npckat']) ? 'selected' : '';
						$kat_select .= '<option value="' . htmlspecialchars($kategorie) . '" ' . $selected . '>' . htmlspecialchars($kategorie) . '</option>';
					}
			
			
					//Altersspanne holen 
					//Altersspanne aus Einstellungen holen - siehe oben		
					$ages = array_map('trim', $ages); // Entfernt Leerzeichen vom Alter

					$ages = explode(",", $settingage); // mit explode werden sie einzeln rausgeholt
					$ages = array_map('trim', $ages); // Entfernt Leerzeichen vom Alter

					//Leeren für PHP 8
					$age_select ='';
					foreach ($ages as $age) {
						$selected = ($age == $npc['npcage']) ? 'selected' : '';
						$age_select .= '<option value="' . htmlspecialchars($age) . '" ' . $selected . '>' .  htmlspecialchars($age) . ' Jahre</option>';
					}

					//Geschlecht 
					//Geschlecht  aus Einstellungen holen - siehe oben		
					$genders = array_map('trim', $genders); // Entfernt Leerzeichen von den Geschlechtern

					$genders = explode(",", $settinggender); // mit explode werden sie einzeln rausgeholt
					$genders = array_map('trim', $genders); // Entfernt Leerzeichen von den Geschlechtern

					//Leeren für PHP 8
					$gender_select ='';
					foreach ($genders as $gender) {
						$selected = ($gender == $npc['npcgender']) ? 'selected' : '';
						$gender_select .= '<option value="' . htmlspecialchars($gender) . '" ' . $selected . '>' . htmlspecialchars($gender) . '</option>';
					}
				
				// Bild anzeigen, wenn vorhanden
					$entryimage = '';
					if (!empty($npc['npcimage'])) {
						$entryimage = '<img src="uploads/npcdb/' . htmlspecialchars($npc['npcimage']) . '" alt="' . htmlspecialchars($npc['npcname']) . '" style="max-width: ' . $settingimgwidth . 'px; max-height: ' . $settingimgheight . 'px;">';
					} else {
						$entryimage = '<p>Kein Bild hochgeladen.</p>'; // Optional: Nachricht, wenn kein Bild vorhanden ist
					}

				// Bearbeitungsformular anzeigen
				eval("\$page = \"" . $templates->get("npcdb_edit") . "\";"); // Template für das Bearbeitungsformular
				output_page($page);
				exit; 
			}
		
// Überprüfen, ob die Aktion update_npc angeklickt wurde
if ($mybb->get_input('action') == "update_npc") {
    $npcid = (int)$mybb->get_input('npcid');

    // Sicherheitsüberprüfung: Überprüfen des my_post_key
    if ($mybb->input['my_post_key'] == $mybb->post_code) {

        // Basisdaten vorbereiten
        $updated_entry = [
            "npcname" => $db->escape_string($mybb->get_input('npcname')),
            "npcdesc" => $db->escape_string($mybb->get_input('npcdesc')),
            "npcage" => $db->escape_string($mybb->get_input('age')),
            "npckat" => $db->escape_string($mybb->get_input('kat')),
            "npcgender" => $db->escape_string($mybb->get_input('gender')),
        ];

        // Bild-Upload verarbeiten
        if (isset($_FILES['npcimage']) && $_FILES['npcimage']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/npcdb/';
            $extension = pathinfo($_FILES['npcimage']['name'], PATHINFO_EXTENSION);
            $unique_name = uniqid('npc_', true) . '.' . $extension;
            $target_file = $upload_dir . $unique_name;

            $check = getimagesize($_FILES['npcimage']['tmp_name']);
            if ($check !== false) {
                if (move_uploaded_file($_FILES['npcimage']['tmp_name'], $target_file)) {

                    // Altes Bild abrufen
                    $query = $db->simple_select("npcdb", "npcimage", "npcid = '{$npcid}'");
                    $old_image = $db->fetch_field($query, "npcimage");

                    // Altes Bild löschen, wenn vorhanden
                    if (!empty($old_image) && file_exists($upload_dir . $old_image)) {
                        unlink($upload_dir . $old_image);
                    }

                    // Neues Bild speichern
                    $updated_entry['npcimage'] = $db->escape_string($unique_name);
                } else {
                    echo "Fehler beim Hochladen des Bildes.";
                }
            } else {
                echo "Die Datei ist kein gültiges Bild.";
            }
        }

        // NPC-Daten aktualisieren
        $db->update_query("npcdb", $updated_entry, "npcid = '{$npcid}'");

        // Weiterleitung
        redirect('npcdb.php', 'NPC erfolgreich aktualisiert');
        exit;
    } else {
        redirect('npcdb.php', 'NPC NICHT aktualisiert');
        exit;
    }
}

			
			
			//NPC Hinzufügen - das Formular!		
			if (is_member($settingcreate)) {
				// Nicht erlaubt und dennoch hier? Weg mit ihm zur Hauptseite
				if (!is_member($settingcreate)) {
					redirect('npcdb.php', $lang->npcdb_error_new);
					return;
				}

				//Kategorien holen 
					//Kategorien  aus Einstellungen holen - siehe oben		
					$kategorien = array_map('trim', $kategorien); // Entfernt Leerzeichen von den Kategorien

					$kategorien = explode(",", $settingkats); // mit explode werden sie einzeln rausgeholt
					$kategorien = array_map('trim', $kategorien); // Entfernt Leerzeichen von den Kategorien

					//Leeren für PHP 8
					$kat_select ='';
					foreach ($kategorien as $kategorie) {
						$kat_select .= '<option value="' . htmlspecialchars($kategorie) . '">' . htmlspecialchars($kategorie) . '</option>';
					}

				//Altersspanne holen 
					//Altersspanne aus Einstellungen holen - siehe oben		
					$ages = array_map('trim', $ages); // Entfernt Leerzeichen vom Alter

					$ages = explode(",", $settingage); // mit explode werden sie einzeln rausgeholt
					$ages = array_map('trim', $ages); // Entfernt Leerzeichen vom Alter

					//Leeren für PHP 8
					$age_select ='';
					foreach ($ages as $age) {
						$age_select .= '<option value="' . htmlspecialchars($age) . '">' . htmlspecialchars($age) . ' Jahre</option>';
					}

				//Geschlecht 
					//Geschlecht  aus Einstellungen holen - siehe oben		
					$genders = array_map('trim', $genders); // Entfernt Leerzeichen von den Geschlechtern

					$genders = explode(",", $settinggender); // mit explode werden sie einzeln rausgeholt
					$genders = array_map('trim', $genders); // Entfernt Leerzeichen von den Geschlechtern

					//Leeren für PHP 8
					$gender_select ='';
					foreach ($genders as $gender) {
						$gender_select .= '<option value="' . htmlspecialchars($gender) . '">' . htmlspecialchars($gender) . '</option>';
					}

					// Platzhalter ersetzen
					$img_text = str_replace(['{settingimgheight}', '{settingimgwidth}'], [$settingimgheight, $settingimgwidth], $lang->npcdb_new_imgtext);

			eval("\$npcdb_new_content = \"".$templates->get("npcdb_new")."\";");
			}		

			//Altersspannen Filter
			$age_options = '';
			foreach ($ages as $age) {
				$age = trim($age);
				$age_options .= '<option value="' . htmlspecialchars($age) . '">' . htmlspecialchars($age) . ' Jahre</option>';
			}

			// Kategorie Filter
			$kate_options = '';
			foreach ($kategorien as $kate) {
				$kate = trim($kate);
				$kate_options .= '<option value="' . htmlspecialchars($kate) . '">' . htmlspecialchars($kate) . '</option>';
			}

			// Gender Filter
			$genders_options = '';
			foreach ($genders as $gender) {
				$gender = trim($gender);
				$gender_options .= '<option value="' . htmlspecialchars($gender) . '">' . htmlspecialchars($gender) . '</option>';
			}

			//Für die filter
			$filter_age = $db->escape_string($mybb->get_input('filter_age'));
			if(empty($filter_age)) {
				$filter_age = "%";
			}

			$filter_kat = $db->escape_string($mybb->get_input('filter_kat'));
			if(empty($filter_kat)) {
				$filter_kat = "%";
			}

			$filter_gender = $db->escape_string($mybb->get_input('filter_gender'));
			if(empty($filter_gender)) {
				$filter_gender = "%";
			}
			if ($settingfilter == 1) {			
				eval("\$npcdb_filter = \"".$templates->get("npcdb_filter")."\";");
			}
			else {
				$npcdb_filter = '';
			}

			//Die einzelnen NPCs				
			$entry_query = "SELECT * FROM " . TABLE_PREFIX . "npcdb WHERE npcage LIKE '$filter_age' AND npckat LIKE '$filter_kat' AND npcgender LIKE '$filter_gender' ORDER BY npckat ASC";
			$query_entry = $db->query($entry_query);

			while ($npc = $db->fetch_array($query_entry)) {

				//Wir holen uns jetzt mal die Daten 
					//und lassen sie erst mal Leerlaufen für PHP 8
					$entryage = "";
					$entrydesc = "";
					$entrygender = "";
					$entryname ="";
					$entrykat ="";
					$entryimage = "";

					//Mit Infos füllen
					$entryage = $npc['npcage'] . " Jahre";
					$entrydesc = $parser->parse_message($npc['npcdesc'], $parser_options);
					$entrygender = $npc['npcgender'];
					$entryname = $npc['npcname'];
					$entrykat = $npc['npckat'];		

					//BILDER
					//Wenn Gäste kein Bild sehen dürfen 
					if ($settingimgguest == 0  AND $mybb->user['uid'] == 0) {

						if ($settingimgguest_nopic == 1) {
							$entryimage = '<img src="' . $settingimgguest_pic . '">';
						} else {
							$entryimage = "";
						}
					}
					else {								
						//Image
						if (!empty($npc['npcimage'])) {
							$entryimage = '<img src="uploads/npcdb/' . htmlspecialchars($npc['npcimage']) . '" alt="' . htmlspecialchars($npc['npcname']) . ' " style="max-width: ' . $settingimgwidth . 'px; max-height:  ' . $settingimgwidth . 'px;">';
							}
						else
						{
							$entryimage = '<img src="' . $settingimgguest_pic . '" style="max-width: ' . $settingimgwidth . 'px; max-height:  ' . $settingimgwidth . 'px;">';
						}
					}	


			//Wenn Usergruppe NPCs löschen kann, dann... 

			
			// Aktuellen Benutzer ermitteln
			$aktuelleuid = (int)$mybb->user['uid']; // Das ist die UID des eingeloggten Users
			$creatorUid = (int)$npc['npccreater'];  // Das ist der Ersteller des aktuellen NPC

			$npcdelete ="";
			$npcedit ="";

			// Löschlink erstellen: Nur für Ersteller oder berechtigte Gruppen
			if ($mybb->user['uid'] == $npc['npccreater'] || is_member($settingdelete)) {
				$npcdelete = "<a href=\"npcdb.php?action=npcdelete&npcid={$npc['npcid']}&my_post_key={$mybb->post_code}\">x</a>";
			} else {
				$npcdelete = "";
			}
			
			// Wenn Usergruppe NPCs bearbeiten kann, dann...
			if ($settingedit) {
				$npcedit = "<a href=\"npcdb.php?action=npcedit&npcid={$npc['npcid']}&my_post_key={$mybb->post_code}\">E</a>";
			} else {
				$npcedit = ""; // Optional: Wenn das Bearbeiten nicht erlaubt ist
			}
			

			//laden des Templates
			eval("\$npcview .= \"" . $templates->get("npcdb_bit") . "\";"); //Template für die Einzelausgabe
			}	

			
			eval('$page = "'.$templates->get('npcdb_main').'";'); // Template der Hauptseite
			output_page($page);
		}
	}