<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


include ("inc.php3");
include_ecrire ("inc_acces.php3");


//
// Action : supprimer un auteur
//
if ($supp && ($connect_statut == '0minirezo'))
	spip_query("UPDATE spip_auteurs SET statut='5poubelle' WHERE id_auteur=$supp");

$retour = "auteurs.php3?";
if (!$tri) $tri='nom';
$retour .= "tri=$tri";
if ($tri=='nom' OR $tri=='statut')
	$partri = " "._T('info_par_tri', array('tri' => $tri));
else if ($tri=='nombre')
	$partri = " "._T('info_par_nombre_article');

if ($visiteurs == "oui") {
	debut_page(_T('titre_page_auteurs'),"auteurs","redacteurs");
	$retour .= '&visiteurs=oui';
} else
	debut_page(_T('info_auteurs_par_tri', array('partri' => $partri)),"auteurs","redacteurs");

debut_gauche();



debut_boite_info();
if ($visiteurs == "oui")
	echo "<p class='arial1'>"._T('info_gauche_visiteurs_enregistres');
else {
	echo "<p class='arial1'>"._T('info_gauche_auteurs');

	if ($connect_statut == '0minirezo')
		echo '<br>'. _T('info_gauche_auteurs_exterieurs');
}
fin_boite_info();


if ($connect_statut == '0minirezo') {
	$query = "SELECT id_auteur FROM spip_auteurs WHERE statut='6forum' LIMIT 1";
	$result = spip_query($query);
	$flag_visiteurs = spip_num_rows($result) > 0;

	debut_raccourcis();
	icone_horizontale(_T('icone_creer_nouvel_auteur'), "auteur_infos.php3?new=oui", "auteur-24.gif", "creer.gif");
	icone_horizontale(_T('icone_informations_personnelles'), "auteurs_edit.php3?id_auteur=$connect_id_auteur", "fiche-perso-24.gif","rien.gif");
	if ($flag_visiteurs) {
		if ($visiteurs == "oui")
			icone_horizontale (_T('icone_afficher_auteurs'), "auteurs.php3", "auteur-24.gif", "");
		else
			icone_horizontale (_T('icone_afficher_visiteurs'), "auteurs.php3?visiteurs=oui", "auteur-24.gif", "");
	}
	fin_raccourcis();
}
debut_droite();


//
// Construire la requete
//

// si on n'est pas minirezo, supprimer les auteurs sans article publie
// sauf les admins, toujours visibles.
// limiter les statuts affiches
if ($connect_statut == '0minirezo') {
	if ($visiteurs == "oui") {
		$sql_visible = "aut.statut IN ('6forum','5poubelle')";
		$tri = 'nom';
	} else {
		$sql_visible = "aut.statut IN ('0minirezo','1comite','5poubelle')";
	}
} else {
	$sql_visible = "(
		aut.statut = '0minirezo'
		OR art.statut IN ('prop', 'publie')
	)";
}

$sql_sel = '';

// tri
switch ($tri) {
case 'nombre':
	$sql_order = ' ORDER BY compteur DESC, unom';
	$type_requete = 'nombre';
	break;

case 'statut':
	$sql_order = ' ORDER BY statut, login = "", unom';
	$type_requete = 'auteur';
	break;

case 'nom':
default:
	$type_requete = 'auteur';
	$sql_sel = ", ".creer_objet_multi ("nom", $spip_lang);
	$sql_order = " ORDER BY multi";
}



//
// La requete de base est tres sympa
//

$query = "SELECT
	aut.id_auteur AS id_auteur,
	aut.statut AS statut,
	aut.login AS login,
	aut.nom AS nom,
	aut.email AS email,
	aut.url_site AS url_site,
	aut.messagerie AS messagerie,
	UPPER(aut.nom) AS unom,
	count(lien.id_article) as compteur
	$sql_sel
FROM spip_auteurs as aut
LEFT JOIN spip_auteurs_articles AS lien ON aut.id_auteur=lien.id_auteur
LEFT JOIN spip_articles AS art ON (lien.id_article = art.id_article)
WHERE
	$sql_visible
GROUP BY aut.id_auteur
$sql_order";


$t = spip_query($query);
$nombre_auteurs = spip_num_rows($t);

//
// Lire les auteurs qui nous interessent
// et memoriser la liste des lettres initiales
//

$max_par_page = 30;
if ($debut > $nombre_auteurs - $max_par_page)
	$debut = max(0,$nombre_auteurs - $max_par_page);
$debut = intval($debut);

$i = 0;
$auteurs=array();
while ($auteur = spip_fetch_array($t)) {
	if ($i>=$debut AND $i<$debut+$max_par_page) {
		if ($auteur['statut'] == '0minirezo')
			$auteur['restreint'] = spip_num_rows(
				spip_query("SELECT * FROM spip_auteurs_rubriques
				WHERE id_auteur=".$auteur['id_auteur']));
			$auteurs[] = $auteur;
	}
	$i++;

	if ($tri == 'nom') {
		$lettres_nombre_auteurs ++;
		$premiere_lettre = strtoupper(spip_substr(extraire_multi($auteur['nom']),0,1));
		if ($premiere_lettre != $lettre_prec) {
#			echo " - $auteur[nom] -";
			$lettre[$premiere_lettre] = $lettres_nombre_auteurs-1;
		}
		$lettre_prec = $premiere_lettre;
	}
}



//
// Affichage
//

echo "<br>";
if ($visiteurs=='oui')
	gros_titre(_T('info_visiteurs'));
else
	gros_titre(_T('info_auteurs'));
echo "<p>";


debut_cadre_relief('auteur-24.gif');
echo "<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=0 WIDTH='100%' class='arial2' style='border: 1px solid #aaaaaa;'>\n";
echo "<tr bgcolor='#DBE1C5'>";
echo "<td width='20'>";
if ($tri=='statut')
  echo http_img_pack('admin-12.gif','', "border='0'");
 else
   echo http_href_img('auteurs.php3?tri=statut','admin-12.gif', "border='0'", _T('lien_trier_statut'));

echo "</td><td>";
	if ($tri == '' OR $tri=='nom')
		echo '<b>'._T('info_nom').'</b>';
	else
		echo "<a href='auteurs.php3?tri=nom' title='"._T('lien_trier_nom')."'>"._T('info_nom')."</a>";

if ($options == 'avancees') echo "</td><td colspan='2'>"._T('info_contact');
echo "</td><td>";
	if ($visiteurs != 'oui') {
		if ($tri=='nombre')
			echo '<b>'._T('info_articles').'</b>';
		else
			echo "<a href='auteurs.php3?tri=nombre' title=\""._T('lien_trier_nombre_articles')."\">"._T('info_articles_2')."</a>"; //'
	}
echo "</td></tr>\n";

if ($nombre_auteurs > $max_par_page) {
	echo "<tr bgcolor='white'><td class='arial1' colspan='".($options == 'avancees' ? 5 : 3)."'>";
	//echo "<font face='Verdana,Arial,Sans,sans-serif' size='2'>";
	for ($j=0; $j < $nombre_auteurs; $j+=$max_par_page) {
		if ($j > 0) echo " | ";

		if ($j == $debut)
			echo "<b>$j</b>";
		else if ($j > 0)
			echo "<a href=$retour&debut=$j>$j</a>";
		else
			echo " <a href=$retour>0</a>";

		if ($debut > $j  AND $debut < $j+$max_par_page){
			echo " | <b>$debut</b>";
		}

	}
	//echo "</font>";
	echo "</td></tr>\n";

	if ($tri == 'nom' AND $options == 'avancees') {
		// affichage des lettres
		echo "<tr bgcolor='white'><td class='arial11' colspan='5'>";
		foreach ($lettre as $key => $val) {
			if ($val == $debut)
				echo "<b>$key</b> ";
			else
				echo "<a href=$retour&debut=$val>$key</a> ";
		}
		echo "</td></tr>\n";
	}
	echo "<tr height='5'></tr>";
}


foreach ($auteurs as $row) {
	// couleur de ligne
	$couleur = ($i % 2) ? '#FFFFFF' : $couleur_claire;
	echo "<tr style='background-color: #eeeeee;'>";

	// statut auteur
	echo "<td style='border-top: 1px solid #cccccc;'>";
	echo bonhomme_statut($row);

	// nom
	echo "</td><td class='verdana11' style='border-top: 1px solid #cccccc;'>";
	echo "<a href='auteurs_edit.php3?id_auteur=".$row['id_auteur']."'>".typo($row['nom']).'</a>';

	if ($connect_statut == '0minirezo' AND $row['restreint'])
		echo " &nbsp;<small>"._T('statut_admin_restreint')."</small>";


	// contact
	if ($options == 'avancees') {
		echo "</td><td class='arial1' style='border-top: 1px solid #cccccc;'>";
		if ($row['messagerie'] != 'non' AND $row['login']
		AND $activer_messagerie != "non" AND $connect_activer_messagerie != "non" AND $messagerie != "non")
			echo bouton_imessage($row['id_auteur'],"force")."&nbsp;";
		if ($connect_statut=="0minirezo")
			if (strlen($row['email'])>3)
				echo "<A HREF='mailto:".$row['email']."'>"._T('lien_email')."</A>";
			else
				echo "&nbsp;";

		if (strlen($row['url_site'])>3)
			echo "</td><td class='arial1' style='border-top: 1px solid #cccccc;'><A HREF='".$row['url_site']."'>"._T('lien_site')."</A>";
		else
			echo "</td><td style='border-top: 1px solid #cccccc;'>&nbsp;";
	}

	// nombre d'articles
	echo "</td><td class='arial1' style='border-top: 1px solid #cccccc;'>";
	if ($row['compteur'] > 1)
		echo $row['compteur']."&nbsp;"._T('info_article_2');
	else if($row['compteur'] == 1)
		echo "1&nbsp;"._T('info_article');
	else
		echo "&nbsp;";

	echo "</td></tr>\n";
}

echo "</table>\n";


echo "<a name='bas'>";
echo "<table width='100%' border='0'>";

$debut_suivant = $debut + $max_par_page;
if ($debut_suivant < $nombre_auteurs OR $debut > 0) {
	echo "<tr height='10'></tr>";
	echo "<tr bgcolor='white'><td align='left'>";
	if ($debut > 0) {
		$debut_prec = strval(max($debut - $max_par_page, 0));
		$link = new Link;
		$link->addVar('debut', $debut_prec);
		echo $link->getForm('GET');
		echo "<input type='submit' name='submit' value='&lt;&lt;&lt;' class='fondo'>";
		echo "</form>";
		//echo "<a href='$retour&debut=$debut_prec'>&lt;&lt;&lt;</a>";
	}
	echo "</td><td style='text-align: $spip_lang_right'>";
	if ($debut_suivant < $nombre_auteurs) {
		$link = new Link;
		$link->addVar('debut', $debut_suivant);
		echo $link->getForm('GET');
		echo "<input type='submit' name='submit' value='&gt;&gt;&gt;' class='fondo'>";
		echo "</form>";
		//echo "<a href='$retour&debut=$debut_suivant'>&gt;&gt;&gt;</a>";
	}
	echo "</td></tr>\n";
}

echo "</table>\n";



fin_cadre_relief();


fin_page();

?>
