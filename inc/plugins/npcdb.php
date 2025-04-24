<?php
//error_reporting ( -1 );
//ini_set ( 'display_errors', true );

//Direkten Zugriff auf diese Datei aus Sicherheitsgründen nicht zulassen
if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
//Informationen zum Plugin
function npcdb_info()
{
	global $lang;
	$lang->load('npcdb');
	
	return array(
		'name' => $lang->npcdb_name,
		'description' => $lang->npcdb_desc_acp,
		'author' => "saen",
		'authorsite' => "https://github.com/saen91",
		'version' => "1.0",
		'compatibility' => "18*"
	);
}

// Diese Funktion installiert das Plugin
function npcdb_install()
{
	global $db, $cache, $mybb;
	
	//LEGE DATENBANK TABELLE AN  npcdb
	$db->write_query("
	CREATE TABLE " . TABLE_PREFIX . "npcdb (
		`npcid` int(11)  NOT NULL auto_increment, 
		`npccreater` int(144)  NOT NULL, 
		`npcname` varchar(500) CHARACTER SET utf8 NOT NULL,
		`npcdesc` longtext CHARACTER SET utf8 NOT NULL,
		`npckat` varchar (140) NOT NULL, 
		`npcage` varchar (140) NOT NULL, 
		`npcgender` varchar (140) NOT NULL,		
		`npcimage` varchar (255) NOT NULL,
		PRIMARY KEY (`npcid`)
		)
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1
    ");
	
	//LEGE DATENBANK TABELLE AN  npcdb_posts
	$db->write_query("
	CREATE TABLE " . TABLE_PREFIX . "npcdb_posts (
		`npcpid` int(11)  NOT NULL auto_increment, 
		`pid` int(10)  NOT NULL, 
		`tid` int(10)  NOT NULL, 
		`npcid` int(11)  NOT NULL, 
		`npcname` varchar(255) CHARACTER SET utf8 NOT NULL,	
		`npcimage` varchar (255) NOT NULL,
		`npcdesc` varchar (255) NOT NULL,
		PRIMARY KEY (`npcpid`)
		)
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1
    ");
	
	//Anlegen vom Uploadordner
	if (!file_exists(MYBB_ROOT . 'uploads/npcdb'))
	{
		mkdir(MYBB_ROOT . 'uploads/npcdb', 0755, true);
	}

	// EINSTELLUNGEN anlegen - Gruppe anlegen
		$setting_group = array (
			'name' => 'npcdb',
			'title' =>  'NPC Datenbank light',
			'description' =>  'Einstellungen für die NPC Datenbank',
			'disporder' => 1,
			'isdefault' => 0
		);

		$gid = $db->insert_query("settinggroups", $setting_group);
	
		//Die dazugehörigen einstellungen
			$setting_array = array(
				
				// Einstellen wer hinterlegen kann
				'npcdb_create' => array(
					'title' => 'Auswahl der Usergruppe: erstellen',
					'description' => 'Welche Usergruppe soll NPCs erstellen können?',
					'optionscode' => 'groupselect',
					'value' => '2,4',
					'disporder' => 1
				) , 
				
				'npcdb_edit' => array(
					'title' => 'Auswahl der Usergruppe: editieren',
					'description' => 'Welche Usergruppe soll NPCs editieren können?',
					'optionscode' => 'groupselect',
					'value' => '2,4',
					'disporder' => 2
				) ,
				
				'npcdb_delete' => array(
					'title' => 'Auswahl der Usergruppe: löschen',
					'description' => 'Welche Usergruppe soll NPCs - ihre eigenen - löschen können?',
					'optionscode' => 'groupselect',
					'value' => '2,4',
					'disporder' => 3
				) ,
				
				//Einstellen der Kategorien
				'npcdb_kats' => array(
					'title' => 'Kategorien',
					'description' => 'Welche Kategorien gibt es auf der Seite? Getrennt mit einem Komma (ohne Leerzeichen)',
					'optionscode' => 'text',
					'value' => 'kategorie,kategorie',
					'disporder' => 4
				) , 
				
				//Einstellen der Altersspanne
				'npcdb_age' => array(
					'title' => 'Altersspanne',
					'description' => 'Gib hier die Altersspanne an, getrennt mit einem Komma (ohne Leerzeichen).',
					'optionscode' => 'text',
					'value' => '10 bis 20,21 bis 30,31 bis 40,41 bis 50,ü50',
					'disporder' => 5
				) , 
				
				//Einstellen des Geschlechts
				'npcdb_gender' => array(
					'title' => 'Geschlecht',
					'description' => 'Gib hier die Geschlechter an, die Auswählbar sind.',
					'optionscode' => 'text',
					'value' => 'weiblich,männlich,non-binär,offen',
					'disporder' => 6
				) , 
				
				//Einstellen, ob Gäste es sehen können
				'npcdb_guest' => array(
					'title' => 'Gastansicht',
					'description' => 'Können Gäste diese Seite sehen?',
					'optionscode' => 'yesno',
	   				 'value' => '1', // Default
					'disporder' => 7
				) , 
				
				//Einstellen, ob Filter ja oder nein
				'npcdb_filter' => array(
					'title' => 'Filter',
					'description' => 'Möchtest du einen Filter für Altersspanne, Geschlecht und Kategorien?',
					'optionscode' => 'yesno',
	   				 'value' => '1', // Default
					'disporder' => 8
				) , 
				
				
				//Einstellen, ob Filter ja oder nein
				'npcdb_image' => array(
					'title' => 'Bilder',
					'description' => 'Möchtest du, dass User Bilder hochladen können?',
					'optionscode' => 'yesno',
	   				 'value' => '1', // Default
					'disporder' => 9
				) , 
				
				//Einstellung kommt nur, wenn vorher "ja" ausgewählt ist
				'npcdb_image_height' => array(
					'title' => 'Bilder Höhe',
					'description' => 'Bitte gib hier die Höhe der Bilder an. Nur die Zahl ohne px',
					'optionscode' => 'text',
					'value' => '200',
					'disporder' => 10
				) , 
				
				//Einstellung kommt nur, wenn vorher "ja" ausgewählt ist
				'npcdb_image_weight' => array(
					'title' => 'Bilder Breite',
					'description' => 'Bitte gib hier die Breite der Bilder an. Nur die Zahl ohne px',
					'optionscode' => 'text',
					'value' => '200',
					'disporder' => 11
				) , 
				
				//Einstellen, ob Gäste Bild sehen oder nicht
				'npcdb_image_guest' => array(
					'title' => 'Bilder für Gäste sichtbar',
					'description' => 'Sollen Gäste das Bild sehen?',
					'optionscode' => 'yesno',
	   				 'value' => '1', // Default
					'disporder' => 12
				) , 
				
				//Einstellung kommt nur, wenn Gäste Bilder nicht sehen dürfen
				'npcdb_image_guest_quest' => array(
					'title' => 'No-Image oder nichts',
					'description' => 'Soll den Gästen ein No-image sehen? Wenn nein, bekommen sie kein Bild angezeigt',
					'optionscode' => 'yesno',
	   				 'value' => '1', // Default
					'disporder' => 13
				) , 
				
				//Einstellung kommt nur, wenn vorher ja ausgewählt wurde
				'npcdb_image_guest_noava' => array(
					'title' => 'No-Image Link',
					'description' => 'Nur den Link angeben',
					'optionscode' => 'text',
	   				 'value' => 'https://ichbineinlink.de/image.png', // Default
					'disporder' => 14
				) , 
				
				//Einstellung kommt nur, wenn vorher ja ausgewählt wurde
				'npcdb_npcaccount' => array(
					'title' => 'NPC Account',
					'description' => 'Gib hier die ID des NPC Accounts ein',
					'optionscode' => 'text',
	   				 'value' => '457', // Default
					'disporder' => 15
				) , 
					
			);

			foreach ($setting_array as $name => $setting)
			{
				$setting['name'] = $name;
				$setting['gid'] = $gid;

				$db->insert_query('settings', $setting);
			}

			rebuild_settings();
	
			//Damit hochgeladen werden kann muss die Berechtigung gesetzt werden
			if (!is_writable(MYBB_ROOT . 'uploads/npcdb/')) {
        	@chmod(MYBB_ROOT . 'uploads/npcdb/', 0755);
			}
	
	//TEMPLATES EINFÜGEN 
	
		//Hauptseite
		$insert_array = array(
			'title'        => 'npcdb_main',
			'template'    => $db->escape_string('<html>
		<head>
		<title>{$settings[\'bbname\']} - {$lang->npcdb_titel}</title>
		{$headerinclude}
		</head>
			<body>
			{$header}
				<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
					<tr>
						<td class="thead"><strong>{$lang->npcdb_titel}</strong></td>
					</tr>
					<tr>
						<td class="trow1">
							<center>{$lang->npcdb_desc}</center>
							{$npcdb_filter}<br>
							<div class="npcdb_container">{$npcview}</div>
							<div class="npcdb_button">{$npcdb_new_content}</div>
						</td>
					</tr>
				</table>
			{$footer}
			</body>
		</html>'),
			'sid'        => '-1',
			'version'    => '',
			'dateline'    => TIME_NOW
		);
		$db->insert_query("templates", $insert_array);
	
	
		//Gastseite
		$insert_array = array(
			'title'        => 'npcdb_guest',
			'template'    => $db->escape_string('<html>
		<head>
		<title>{$settings[\'bbname\']} - {$lang->npcdb_titel}</title>
		{$headerinclude}
		</head>
			<body>
			{$header}
				<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
					<tr>
						<td class="thead"><strong>{$lang->npcdb_titel}</strong></td>
					</tr>
					<tr>
						<td class="trow1" align="center">
							{$lang->npcdb_desc_guest}
						</td>
					</tr>
				</table>
			{$footer}
			</body>
		</html>'),
			'sid'        => '-1',
			'version'    => '',
			'dateline'    => TIME_NOW
		);
		$db->insert_query("templates", $insert_array);	
	
	//Neuer NPC
		$insert_array = array(
			'title'        => 'npcdb_new',
			'template'    => $db->escape_string('
				<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
					<tr>
						<td class="thead"><strong>{$lang->npcdb_titel_new}</strong></td>
					</tr>
					<tr>
						<td class="trow1">
							<center>{$lang->npcdb_new_desc}</center>
							<form action="npcdb.php?action=save_npc" method="post" enctype="multipart/form-data">
								
								<table width="100%">
									<tr>
										<td width="25%" style="text-align:center;">
											<h2><label for="npcname">{$lang->npcdb_new_name}</label></h2>
											<input type="text" name="npcname" id="npcname" required>
										</td>
										<td width="25%" style="text-align:center;">
											<h2><label for="gender">{$lang->npcdb_new_gender}</label></h2>
											<select name="gender" id="gender" required>
												{$gender_select} <!-- Option-Auswahl für die Altersspanne -->
											</select>
										</td>
										<td width="25%" style="text-align:center;">
											<h2><label for="age">{$lang->npcdb_new_age}</label></h2>
											<select name="age" id="age" required>
												{$age_select} <!-- Option-Auswahl für die Altersspanne -->
											</select>
										</td>
										<td width="25%" style="text-align:center;">
											<h2><label for="kat">{$lang->npcdb_new_kat}</label></h2>
											<select name="kat" id="kat" required>
												{$kat_select} <!-- Option-Auswahl für die Kategorien -->
											</select>
										</td>
									</tr>
									<tr>
										<td colspan="4" style="text-align:center;">
											<h2><label for="npcimage">{$lang->npcdb_new_img}</label></h2>		
											{$img_text}
											<input type="file" name="npcimage" id="npcimage" accept="image/*"> 
										</td>
									</tr>
									<tr>
										<td colspan="4">
											<h2><label for="npcdesc">{$lang->npcdb_new_besch}</label></h2>
											<center><textarea name="npcdesc" id="npcdesc" required style="width:80%;height:200px;"></textarea></center>
										</td>
									</tr>
									<tr>
										<td colspan="4">
											<center>
												<input type="submit" value="{$lang->npcdb_new_submit}">
											</center>
										</td>
									</tr>
								</table>								
							</form>
						</td>
					</tr>
				</table>
			'),
			'sid'        => '-1',
			'version'    => '',
			'dateline'    => TIME_NOW
		);
		$db->insert_query("templates", $insert_array);	
	
	
	//EDITIEREN
		$insert_array = array(
			'title'        => 'npcdb_edit',
			'template'    => $db->escape_string('<html>
		<head>
		<title>{$settings[\'bbname\']} - {$lang->npcdb_titel_new}</title>
		{$headerinclude}
		</head>
			<body>
			{$header}
				<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
					<tr>
						<td valign="top">
							<table width="100%">								
								<form action="npcdb.php?action=update_npc" method="post" enctype="multipart/form-data">
								<input type="hidden" name="npcid" value="{$npc[\'npcid\']}">
								<input type="hidden" name="my_post_key" value="{$mybb->post_code}">
								<tr>
									<td colspan="4" class="thead">
										{$lang->npcdb_edit_title}										
									</td>
								</tr>
								<tr>
									<td colspan="4">
											<center>Hier hast du nun die Möglichkeit den NPC <b>{$npc[\'npcname\']}</b> zu editieren. Bitte beachte, dass diese Änderung für alle sichtbar sind. </center>				
									</td>
								</tr>
								
									
								<tr>
									<td width="25%" valign="top"><h2><label for="npcname">{$lang->npcdb_new_name}</label></h2>
										<input type="text" name="npcname" id="npcname" value="{$npc[\'npcname\']}" required>
									</td>
									<td width="25%" valign="top"><h2><label for="age">{$lang->npcdb_new_age}</label></h2>
										<select name="age" id="age" required>
											{$age_select} <!-- Hier die Altersoptionen einfügen -->
										</select>	
									</td>
									<td width="25%" valign="top"><h2><label for="kat">{$lang->npcdb_new_kat}</label></h2>
										<select name="kat" id="kat" required>
											{$kat_select} <!-- Hier die Kategorienoptionen einfügen -->
										</select>
									</td>
									<td width="25%" valign="top"><h2><label for="gender">{$lang->npcdb_new_gender}</label></h2>
										<select name="gender" id="gender" required>
											{$gender_select} <!-- Hier die Geschlechtsoptionen einfügen -->
										</select>
									</td>							
								</tr>
								<tr>
									<td colspan="4" style="margin:auto;">
										<h2><label for="npcdesc">{$lang->npcdb_new_besch}</label></h2>
										<textarea name="npcdesc" id="npcdesc"  required style="width:80%;height:200px;">{$npc[\'npcdesc\']}</textarea>
									</td>
								</tr>
								<tr>
									<td colspan="4"><h2><label for="npc_image">{$lang->npcdb_new_img}</label></h2></td>
								</tr>
								<tr>
									<td colspan="2" valign="top"><h2>{$lang->npcdb_edit_pic}</h2>
										<center>{$entryimage}</center>
									</td>
									<td colspan="2" valign="top"><h2>{$lang->npcdb_edit_newpic}</h2>
										<input type="file" name="npcimage" id="npcimage" accept="image/*">
									</td>
								</tr>
								<tr>
									<td colspan="4"><center><input type="submit" value="{$lang->npcdb_edit_submit}"><br><br>
									<a href="npcdb.php">{$lang->npcdb_delete_cancel}</a></center></td>
								 </tr>
                    </form>
                </table>
            </td>
        </tr>
				</table>
			{$footer}
			</body>
		</html>'),
			'sid'        => '-1',
			'version'    => '',
			'dateline'    => TIME_NOW
		);
		$db->insert_query("templates", $insert_array);	
	
	//OKAY FÜR LÖSCHEN
		$insert_array = array(
			'title'        => 'npcdb_confirm_delete',
			'template'    => $db->escape_string('<html>
		<head>
		<title>{$settings[\'bbname\']} - {$lang->npcdb_titel_new}</title>
		{$headerinclude}
		</head>
			<body>
			{$header}
				<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
					<tr>
				<td valign="top">
					<center>
						<h2>{$lang->npcdb_delete_okay}</h2>
							<p>Bist du sicher, dass du den NPC <strong>{$npc[\'npcname\']}</strong> löschen möchtest?</p>
							<form action="npcdb.php?action=confirm_delete" method="post">
								<input type="hidden" name="npcid" value="{$npc[\'npcid\']}">
								<input type="hidden" name="my_post_key" value="{$mybb->post_code}">
								<input type="submit" value="{$lang->npcdb_delete_submit}">
							</form><br>
							<a href="npcdb.php">{$lang->npcdb_delete_cancel}</a>
					</center>
				</td>
		</tr>
				</table>
			{$footer}
			</body>
		</html>'),
			'sid'        => '-1',
			'version'    => '',
			'dateline'    => TIME_NOW
		);
		$db->insert_query("templates", $insert_array);	
	
		//Eintrag
		$insert_array = array(
			'title'        => 'npcdb_bit',
			'template'    => $db->escape_string('<div class="npcdb_container_bit">		
		<h2>{$entryname} <span class="npcdb_edit">{$npcdelete} {$npcedit}</span></h2>
		<div>{$entryimage}</div>
		<div><h2>{$lang->npcdb_bit_age}</h2>{$entryage}</div>
		<div><h2>{$lang->npcdb_bit_kat}</h2>{$entrykat}</div>	
		<div><h2>{$lang->npcdb_bit_gender}</h2>{$entrygender}</div>	
		<div class="npcdb_desc">{$entrydesc}</div>	
	</div>'),
			'sid'        => '-1',
			'version'    => '',
			'dateline'    => TIME_NOW
		);
		$db->insert_query("templates", $insert_array);
	
	//postbit
		$insert_array = array(
			'title'        => 'npcdb_postbit',
			'template'    => $db->escape_string('<div class="npc_postbit_cont">
	<h2><div class="npc_pb_name">{$lang->npcdb_postbit} <i>{$post[\'npcname\']}</i></div></h2>
</div>

<span class="npc_pb_image">{$post[\'npcimage\']}</span>'),
			'sid'        => '-1',
			'version'    => '',
			'dateline'    => TIME_NOW
		);
		$db->insert_query("templates", $insert_array);
	
	
	//Filter
		$insert_array = array(
			'title'        => 'npcdb_filter',
			'template'    => $db->escape_string('<br>
			<center>
				<form method="get" action="npcdb.php">
					<select name="filter_age">
						<option value="">{$lang->npcdb_filter_all_age}</option>
						<?php echo $age_options; ?>
					</select>
					<select name="filter_kat">
						<option value="">{$lang->npcdb_filter_all_kat}</option>
						<?php echo $kate_options; ?>
					</select>
					<select name="filter_gender">
						<option value="">{$lang->npcdb_filter_all_gender}</option>
						<?php echo $gender_options; ?>
					</select>
					<input type="submit" value="Filtern">
				</form>
			</center>'),
			'sid'        => '-1',
			'version'    => '',
			'dateline'    => TIME_NOW
		);
		$db->insert_query("templates", $insert_array);
	
	
	//Filter für Antwort etc. 
		$insert_array = array(
			'title'        => 'npcdb_filternpc',
			'template'    => $db->escape_string('<tr>
	<td class="trow2" width="20%"><strong>{$lang->npcdb_dropdown_title}</strong></td>
	<td class="trow2">
		<select name="npcid" class="select">			
			<option>{$lang->npcdb_dropdown_normal}</option>
			{$npc_bit}
			</option>
		</select>		
	</td>
</tr>'),
			'sid'        => '-1',
			'version'    => '',
			'dateline'    => TIME_NOW
		);
		$db->insert_query("templates", $insert_array);
	
	//CSS hinzufügen 
	$css = array(
		'name' =>'npcdb.css',
		'tid' => 1,
		'attachedto' => '',
		"stylesheet" => '.npcdb_container {
	padding: 20px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 20px;
}

.npcdb_container_bit {
	width: 45%;
	display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
}

.npcdb_desc{
	width:100%;
	text-align:justify;
	max-height:150px;
	overflow:auto;
	margin-top:10px;
	padding-right:5px;
}

.npcdb_edit {
	text-align:left;
}

.npc_postbit_cont {
	width: 100%;
	margin:0px auto 30px auto;
}

.npc_pb_name {
	font-size:10px;
}

.npc_pb_image {
	float: left;
	padding-right:10px;
}',
	'cachefile' => $db->escape_string(str_replace('/', '', 'npcdb.css')),
    'lastmodified' => time()
  );
	 require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
	
	$sid = $db->insert_query("themestylesheets", $css);
  $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . $sid), "sid = '" . $sid . "'", 1);

  $tids = $db->simple_select("themes", "tid");
  while ($theme = $db->fetch_array($tids)) {
    update_theme_stylesheet_list($theme['tid']);
  }
}

// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function npcdb_is_installed(){

    global $db;
    
    if ($db->fetch_array($db->simple_select('settinggroups', '*', "name='npcdb'"))) {
		
        return true;
    }
    return false;
}

// Plugin-Deinstallation
function npcdb_uninstall()
{
    global $db, $cache;
	
	//CSS LÖSCHEN
	  require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
	  $db->delete_query("themestylesheets", "name = 'npcdb.css'");
	  $query = $db->simple_select("themes", "tid");
	  while ($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
	  }
	
    // Lösche die Plugin-Einstellungen
    $db->delete_query('settings', "name LIKE 'npcdb%'");
    $db->delete_query('settinggroups', "name = 'npcdb'");
	
    // Lösche die Vorlagengruppe und das Template
	$db->delete_query("templates", "title LIKE '%npcdb%'");
	
	//Datenbank-Eintrag löschen
		if ($db->table_exists("npcdb"))
		{
			$db->drop_table("npcdb");
		}
	
	rebuild_settings();
}

// Plugin-Aktivierung
function npcdb_activate()
{
	global $db, $mybb;
 
	//damit die variable für die Auswahl automatisch in das TPL übernommen wird
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("newreply", "#".preg_quote('{$loginbox}')."#i", '{$loginbox} {$filternpc}');
	find_replace_templatesets("newthread", "#".preg_quote('{$loginbox}')."#i", '{$loginbox} {$filternpc}');
	find_replace_templatesets("editpost", "#".preg_quote('{$posticons}')."#i", '{$filternpc} {$posticons}');
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'message\']}')."#i", '{$post[\'npc_postbit\']} {$post[\'message\']}');
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'message\']}')."#i", '{$post[\'npc_postbit\']} {$post[\'message\']}');
}

// Plugin-Deaktivierung
function npcdb_deactivate()
{
    global $db, $mybb;

	//Damit die Variable für die Auswahl automatisch wieder rausgenommen wird
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("newreply", "#".preg_quote('{$filternpc}')."#i", '', 0);
	find_replace_templatesets("newthread", "#".preg_quote('{$filternpc}')."#i", '', 0);
	find_replace_templatesets("editpost", "#".preg_quote('{$filternpc}')."#i", '', 0);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'npc_postbit\']}')."#i", '', 0);
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'npc_postbit\']}')."#i", '', 0);
}


//HOOK HAUPTSEITE FÜR NEUES THEMA
$plugins->add_hook("newthread_start", "npcdb_main");
	function npcdb_main()
	{
		global $db, $cache, $mybb, $templates, $theme, $header, $headerinclude, $footer, $page, $misc, $filternpc, $lang;
		
		 $lang->load('npcdb');

		// Einstellungen holen
		$npcaccount = $mybb->settings['npcdb_npcaccount'];

		//Den Dropdown einfügen, wenn man mit dem NPC eingeloggt ist (Vergleich UID und NPC-id aus EInstellungen)
		if ($mybb->user['uid'] == $npcaccount) {  

			//Datenbankabfrage
			$npcfilter = $db->simple_select("npcdb", "*", "");

			if(isset($mybb->input['previewpost']) || $post_errors) {

				$npc = $mybb->get_input('npcid');
			}

			 $npc_bit = ""; // Initialisiere die Variable

				while($npcs = $db->fetch_array($npcfilter)) {

				$selected = "";

				if($npc == $npcs['npcid']) {
					$selected = "selected";
				}


				$npc_bit .= "<option value=\"{$npcs['npcid']}\" {$selected}>{$npcs['npcname']}</option>";
			}

			eval("\$filternpc = \"" . $templates->get("npcdb_filternpc") . "\";");   
		}	
	}



$plugins->add_hook("newthread_do_newthread_end", "npcdb_do_newthread");
	function npcdb_do_newthread()
	{
		global $db, $mybb, $tid, $pid, $new_record;

		// NPC-ID vom Formular abrufen
		$npcid = (int)$mybb->get_input('npcid');

		// NPC-Daten aus der Datenbank abrufen
		$npc_query = $db->simple_select("npcdb", "*", "npcid = '{$npcid}'");
		$npc_data = $db->fetch_array($npc_query);
		
		//Bei einem neuen Thema holt er sich die pid aus dem 1. erstellten Beitrag
		if (empty($pid)) {
			$query = $db->query("SELECT pid FROM `mybb_posts` WHERE tid = ".(int)$tid." ORDER BY pid ASC LIMIT 1;");
			$pid = $db->fetch_field($query, "pid");
		} 
		
		   // Überprüfen, ob NPC-Daten gefunden wurden
		if ($npc_data) {

			// In die neue DB hinterlegen, damit wir später darauf zugreifen können 
			$new_record = [
				"pid" => (int)$pid, // PID des neuen Beitrags
				"tid" => (int)$tid, 
				"npcid" => $npcid,
				"npcname" => $npc_data['npcname'], // NPC-Name aus der Datenbank
				"npcimage" => $npc_data['npcimage'], // NPC-Bild aus der Datenbank
				"npcdesc" => $npc_data['npcdesc'] // Beschreibung aus der Datenbank
			];


			// Daten in die npcdb_posts Tabelle einfügen
			$db->insert_query("npcdb_posts", $new_record);
		}
	}


//HOOK HAUPTSEITE FÜR NEUE ANTWORT
$plugins->add_hook("newreply_start", "npcdb_reply");
	function npcdb_reply()
	{
		global $db, $cache, $mybb, $templates, $theme, $header, $headerinclude, $footer, $page, $misc, $filternpc, $lang;
		
		 $lang->load('npcdb');

		// Einstellungen holen
		$npcaccount = $mybb->settings['npcdb_npcaccount'];

		//Den Dropdown einfügen, wenn man mit dem NPC eingeloggt ist (Vergleich UID und NPC-id aus EInstellungen)
		if ($mybb->user['uid'] == $npcaccount) {  

			//Datenbankabfrage
			$npcfilter = $db->simple_select("npcdb", "*", "");

			if(isset($mybb->input['previewpost']) || $post_errors) {

				$npc = $mybb->get_input('npcid');
			}

			 $npc_bit = ""; // Initialisiere die Variable

				while($npcs = $db->fetch_array($npcfilter)) {

				$selected = "";

				if($npc == $npcs['npcid']) {
					$selected = "selected";
				}


				$npc_bit .= "<option value=\"{$npcs['npcid']}\" {$selected}>{$npcs['npcname']}</option>";
			}

			eval("\$filternpc = \"" . $templates->get("npcdb_filternpc") . "\";");   
		}	
	}


$plugins->add_hook("newreply_do_newreply_end", "npcdb_do_newreply");
	function npcdb_do_newreply()
	{
		global $db, $mybb, $tid, $pid, $new_record;

		// NPC-ID vom Formular abrufen
		$npcid = (int)$mybb->get_input('npcid');

		// NPC-Daten aus der Datenbank abrufen
		$npc_query = $db->simple_select("npcdb", "*", "npcid = '{$npcid}'");
		$npc_data = $db->fetch_array($npc_query);

		   // Überprüfen, ob NPC-Daten gefunden wurden
		if ($npc_data) {

			// In die neue DB hinterlegen, damit wir später darauf zugreifen können 
			$new_record2 = [
				"pid" => (int)$pid, // PID des neuen Beitrags
				"tid" => (int)$tid, 
				"npcid" => $npcid,
				"npcname" => $npc_data['npcname'], // NPC-Name aus der Datenbank
				"npcimage" => $npc_data['npcimage'], // NPC-Bild aus der Datenbank
				"npcdesc" => $npc_data['npcdesc'] // Beschreibung aus der Datenbank
			];

			// Daten in die npcdb_posts Tabelle einfügen
			$db->insert_query("npcdb_posts", $new_record2);
		}
	}



//DAS EDIT  && Hook ---> erst mal die Main, damit wir überhaupt die Auswahl bekommen!
$plugins->add_hook("editpost_end", "npcdb_editpost");
function npcdb_editpost()
{
    global $db, $mybb, $lang, $templates, $pid, $tid, $editpost_npc, $filternpc;

    $lang->load('npcdb');

    $editpost_npc = "";

    // Einstellungen holen
    $npcaccount = $mybb->settings['npcdb_npcaccount'];

    // Dropdown nur einfügen, wenn man mit dem NPC eingeloggt ist
    if ($mybb->user['uid'] == $npcaccount) {  

        // DB Abfrage, für aktuellen NPC für diesen Post 
        $npc_post_query = $db->simple_select("npcdb_posts", "npcid", "pid = '{$pid}'");
        $npc_post = $db->fetch_array($npc_post_query);
        $current_npc_id = isset($npc_post['npcid']) ? $npc_post['npcid'] : null;

        // Datenbankabfrage
        $npcfilter = $db->simple_select("npcdb", "*", "");

        // Standardwert für $npc setzen
        $npc = $current_npc_id; // Verwende den aktuellen NPC, wenn vorhanden

        if (isset($mybb->input['previewpost']) || isset($post_errors)) {
            $npc = $mybb->get_input('npcid');
        }

        $npc_bit = ""; // Initialisiere die Variable

        while ($npcs = $db->fetch_array($npcfilter)) {
            $selected = ($npc == $npcs['npcid']) ? "selected" : "";
            $npc_bit .= "<option value=\"{$npcs['npcid']}\" {$selected}>{$npcs['npcname']}</option>";
        }

        eval("\$filternpc = \"" . $templates->get("npcdb_filternpc") . "\";");   
        // Hier sollte die Ausgabe von $filternpc erfolgen, z.B. durch echo oder in das Template einfügen
    }    
}



// DAS EDIT && Hook ---> speichern von einem anderen NPC aus Liste
$plugins->add_hook("editpost_do_editpost_end", "npcdb_do_editpost");

function npcdb_do_editpost() {
	global $db, $mybb, $pid, $tid;

	// NPC-ID vom Formular
	$npcid = $mybb->get_input('npcid');

	// Wenn keine NPC-ID übermittelt wurde, abbrechen
	if ($npcid <= 0) {
		return;
	}

	// Prüfen, ob NPC mit dieser ID existiert 
	// NPC-Daten aus der Datenbank abrufen
	$npc_query = $db->simple_select("npcdb", "*", "npcid = '{$npcid}'");
	$npc_data = $db->fetch_array($npc_query);

	if (!$npc_data) {
		return;
	}

	// Prüfen, ob es schon einen Eintrag für diesen Post gibt
	$check_query = $db->simple_select("npcdb_posts", "pid", "pid = '{$pid}'");
	$existing_entry = $db->fetch_field($check_query, "pid");

	// Daten vorbereiten
	$update_data = [
		"npcid" => $npcid,
		"npcname" => $db->escape_string($npc_data['npcname']),
		"npcimage" => $db->escape_string($npc_data['npcimage']),
		"npcdesc" => $db->escape_string($npc_data['npcdesc'])
	];

	if ($existing_entry) {
		// tid noch nicht gesetzt oder 0, dann ausfüllen
		if (empty($existing_entry['tid'])) {
			$update_data['tid'] = $tid;
		}

		// Eintrag existiert → aktualisiere
		$db->update_query("npcdb_posts", $update_data, "pid = '{$pid}'");
	} else {
		// Kein Eintrag → neuen erstellen
		$update_data['pid'] = $pid;
		$update_data['tid'] = $tid;
		$db->insert_query("npcdb_posts", $update_data);
	}
}

	

// DIE ANZEIGE IM POSTBIT!!!
$plugins->add_hook("postbit", "npcdb_postbit");

function npcdb_postbit(&$post) 
{
    global $db, $mybb, $lang, $templates;

    // Laden der Sprachdatei
    $lang->load("npcdb");    

	//holen der Einstellungen
	 $settingimgwidth = $mybb->settings['npcdb_image_weight'];
	 $settingimgheight = $mybb->settings['npcdb_image_height'];
	
    // NPC-ID für den aktuellen Post abrufen
    $pid = $post['pid']; 
    $npc_query = $db->simple_select("npcdb_posts", "npcid", "pid = '{$pid}'");
    $npc_data = $db->fetch_array($npc_query);

    // Ist hier ein NPC bei der PID zu finden?
    if ($npc_data) {
        $npcid = $npc_data['npcid'];

        // NPC-Daten aus der npcdb abrufen
        $npc_query = $db->simple_select("npcdb", "*", "npcid = '{$npcid}'");
        $npc_info = $db->fetch_array($npc_query);

        // Überprüfen, ob NPC-Informationen gefunden wurden
        if ($npc_info) {
            // NPC-Name und Bild in die Postbit-Ausgabe einfügen
            $post['npcname'] = $npc_info['npcname'];
			$post['npcdesc'] = $npc_info['npcdesc'];
			
			$post['npcimage'] ='';
			if (!empty($npc_info['npcimage'])) {
				$post['npcimage'] = '<img src="uploads/npcdb/' . htmlspecialchars($npc_info['npcimage']) . '" alt="' . htmlspecialchars($npc_info['npcname']) . '" style="max-width: ' . $settingimgwidth . 'px; max-height: ' . $settingimgheight . 'px;">';
			} else {
					$post['npcimage'] = ''; 
				}
 

            // NPC-Template in die Postbit-Ausgabe einfügen
            eval("\$post['npc_postbit'] = \"" . $templates->get("npcdb_postbit") . "\";");
        }
    } else {
        // Wenn kein NPC gefunden wurde, setze die Variable auf leer
        $post['npc_postbit'] = '';
    }
}
	


$plugins->add_hook("admin_settings_print_peekers", "npcdb_settings_peek");
//Für die Einstellungen, damit dort immer dieses "wenn xy ausgewählt wurde, dann..." passiert
	function npcdb_settings_peek(&$peekers)
{
    global $mybb, $npcdb_settings_peeker;

	//für die Höhe
    if ($npcdb_settings_peeker) {
       $peekers[] = 'new Peeker($(".setting_npcdb_image"), $("#row_setting_npcdb_height"),/1/,true)'; //Wenn image mit ja, dann das
    }
	//für die Breite
	if ($npcdb_settings_peeker) {
       $peekers[] = 'new Peeker($(".setting_npcdb_image"), $("#row_setting_npcdb_weight"),/1/,true)'; //Wenn image mit ja, dann das
    }
	//für die Gastsachen
	 if ($npcdb_settings_peeker) {
       $peekers[] = 'new Peeker($(".setting_npcdb_image"), $("#row_setting_npcdb_guest"),/1/,true)'; //Wenn image mit ja, dann das
    }
		// für kein Bild oder doch 
		if ($npcdb_settings_peeker) {
		   $peekers[] = 'new Peeker($(".setting_npcdb_image_guest"), $("#row_setting_npcdb_guest_guest"),/0/,true)'; //Wenn guest mit nein, dann das
		}
		// für no pic oder doch 
		if ($npcdb_settings_peeker) {
		   $peekers[] = 'new Peeker($(".setting_npcdb_image_guest_guest"), $("#row_setting_npcdb_guest_noava"),/1/,true)'; //Wenn guest_guest mit ja, dann das
		}
}


// ONLINE LOCATION
$plugins->add_hook("fetch_wol_activity_end", "npcdb_online_activity");
$plugins->add_hook("build_friendly_wol_location_end", "npcdb_online_location");

function npcdb_online_activity($user_activity) {
global $parameters, $user;

    $split_loc = explode(".php", $user_activity['location']);
    if($split_loc[0] == $user['location']) {
        $filename = '';
    } else {
        $filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
    }
    
    switch ($filename) {
        case 'npcdb':
        if(!isset($parameters['action']))
        {
            $user_activity['activity'] = "npcdb";
        }
        break;
    }
      
return $user_activity;
}

function npcdb_online_location($plugin_array) {
global $mybb, $theme, $lang;

	if($plugin_array['user_activity']['activity'] == "npcdb") {
		$plugin_array['location_name'] = "Sieht sich die <a href=\"npcdb.php\">NPC Datenbank</a> an.";
	}

return $plugin_array;
}
