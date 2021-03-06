<?php
/* Copyright (C) 2020 Thibault FOUCART support@ptibogxiv.net
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *   	\file       htdocs/adherentsplus/admin/about.php
 *      \ingroup    adherentsplus
 *      \brief      Page about
 */

define('NOCSRFCHECK',1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && file_exists("../../../../../main.inc.php")) $res=@include("../../../../../main.inc.php");
if (! $res && preg_match('/\/nltechno([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
dol_include_once('/adherentsplus/lib/member.lib.php');


if (!$user->admin) accessforbidden();

$langs->loadLangs(array('admin', 'adherentsplus@adherentsplus'));

/**
 * View
 */

$help_url='';
llxHeader('','',$help_url);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MembersPlusSetup"),$linkback,'title_setup');

$head = member_admin_prepare_head();

dol_fiche_head($head, 'about', $langs->trans("Members"), -1, 'user');

print $langs->trans("AboutInfo").'<br>';
print '<br>';
$url1='https://github.com/sponsors/ptibogxiv';
$url2='https://www.paypal.me/ptibogxiv';
print '<a href="'.$url1.'" target="_blank">'.$langs->trans("SponsorGithub").'</a><br><br>';
print '<a href="'.$url2.'" target="_blank">'.$langs->trans("SponsorPayPal").'</a><br><br>';
print '<br>';

print $langs->trans("MoreModules").'<br>';
print '&nbsp; &nbsp; &nbsp; '.$langs->trans("MoreModulesLink").'<br>';
$url='http://www.dolistore.com/search.php?search_query=ptibogxiv.net';
print '<a href="'.$url.'" target="_blank"><img border="0" width="180" src="'.DOL_URL_ROOT.'/theme/dolistore_logo.png"></a><br><br><br>';

print '<br>';

dol_fiche_end();


llxFooter();

$db->close();
