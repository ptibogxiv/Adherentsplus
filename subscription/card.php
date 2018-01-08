<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017      Ari Elbaz (elarifr)	<github@accedinfo.com>
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
 */

/**
 *       \file       htdocs/adherentsplus/subscription/card.php
 *       \ingroup    member
 *       \brief      Page to add/edit/remove a member subscription
 */

// require '../../main.inc.php';
// Dolibarr environment
$res = 0;
if (! $res && file_exists("../main.inc.php"))
{
	$res = @include "../main.inc.php";
}
if (! $res && file_exists("../../main.inc.php"))
{
	$res = @include "../../main.inc.php";
}
if (! $res && file_exists("../../../main.inc.php"))
{
	$res = @include "../../../main.inc.php";
}
if (! $res)
{
	die("Main include failed");
}
dol_include_once('/adherentsplus/lib/member.lib.php');
dol_include_once('/adherentsplus/class/adherent.class.php');
dol_include_once('/adherentsplus/class/subscription.class.php');
if (! empty($conf->banque->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
}

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");

$adh = new AdherentPlus($db);
$object = new SubscriptionPlus($db);
$errmsg='';

$action=GETPOST("action",'alpha');
$rowid=GETPOST("rowid","int")?GETPOST("rowid","int"):GETPOST("id","int");
$typeid=GETPOST("typeid","int");
$cancel=GETPOST('cancel');
$confirm=GETPOST('confirm');

if (! $user->rights->adherent->cotisation->lire)
	 accessforbidden();

$permissionnote = $user->rights->adherent->cotisation->creer; // Used by the include of actions_setnotes.inc.php
$permissiondellink=$user->rights->adherent->cotisation->creer;	// Used by the include of actions_dellink.inc.php
$permissiontoedit = $user->rights->adherent->cotisation->creer; // Used by the include of actions_lineupdonw.inc.php


/*
 * 	Actions
 */

if ($cancel) $action='';

//include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once


if ($user->rights->adherent->cotisation->creer && $action == 'update' && ! $cancel)
{
	// Charge objet actuel
	$result=$object->fetch($rowid);
	if ($result > 0)
	{
		$db->begin();

		$errmsg='';

		if ($object->fk_bank)
		{
			$accountline=new AccountLine($db);
			$result=$accountline->fetch($object->fk_bank);

			// If transaction consolidated
			if ($accountline->rappro)
			{
				$errmsg=$langs->trans("SubscriptionLinkedToConciliatedTransaction");
			}
			else
			{
				$accountline->datev=dol_mktime($_POST['datesubhour'], $_POST['datesubmin'], 0, $_POST['datesubmonth'], $_POST['datesubday'], $_POST['datesubyear']);
				$accountline->dateo=dol_mktime($_POST['datesubhour'], $_POST['datesubmin'], 0, $_POST['datesubmonth'], $_POST['datesubday'], $_POST['datesubyear']);
				$accountline->amount=$_POST["amount"];
				$result=$accountline->update($user);
				if ($result < 0)
				{
					$errmsg=$accountline->error;
				}
			}
		}

		if (! $errmsg)
		{
			// Modifie valeures
			$object->dateh=dol_mktime($_POST['datesubhour'], $_POST['datesubmin'], 0, $_POST['datesubmonth'], $_POST['datesubday'], $_POST['datesubyear']);
			$object->datef=dol_mktime($_POST['datesubendhour'], $_POST['datesubendmin'], 0, $_POST['datesubendmonth'], $_POST['datesubendday'], $_POST['datesubendyear']);
			$object->note=$_POST["note"];
			$object->amount=$_POST["amount"];
			//print 'datef='.$object->datef.' '.$_POST['datesubendday'];

			$result=$object->update($user);
			if ($result >= 0 && ! count($object->errors))
			{
				$db->commit();

				header("Location: card.php?rowid=".$object->id);
				exit;
			}
			else
			{
				$db->rollback();

			    if ($object->error)
				{
					$errmsg=$object->error;
				}
				else
				{
					foreach($object->errors as $error)
					{
						if ($errmsg) $errmsg.='<br>';
						$errmsg.=$error;
					}
				}
				$action='';
			}
		}
		else
		{
			$db->rollback();
		}
	}
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->adherent->cotisation->creer)
{
	$result=$object->fetch($rowid);
    $result=$object->delete($user);
    if ($result > 0)
    {
    	header("Location: ".dol_buildpath('/adherentsplus/subscription/list.php',1));
    	exit;
    }
    else
    {
    	$mesg=$adh->error;
    }
}



/*
 * View
 */

$form = new Form($db);


llxHeader('',$langs->trans("SubscriptionCard"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');


dol_htmloutput_errors($errmsg);


if ($user->rights->adherent->cotisation->creer && $action == 'edit')
{
	/********************************************
	 *
	 * Subscription card in edit mode
	 *
	 ********************************************/

    $object->fetch($rowid);
	$result=$adh->fetch($object->fk_adherent);

	$head = subscription_prepare_head($object);

	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"update\">";
	print "<input type=\"hidden\" name=\"rowid\" value=\"$rowid\">";
	print "<input type=\"hidden\" name=\"fk_bank\" value=\"".$object->fk_bank."\">";

	dol_fiche_head($head, 'general', $langs->trans("Subscription"), 0, 'payment');

    $linkback = '<a href="'.dol_buildpath('/adherentsplus/subscription/list.php',1).'">'.$langs->trans("BackToList").'</a>';

  print "\n";
	print '<table class="border" width="100%">';

    // Ref
    print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td>';
	print '<td class="valeur" colspan="3">';
	print $form->showrefnav($object, 'rowid', $linkback, 1);
	print '</td></tr>';

    // Member
	$adh->ref=$adh->getFullName($langs);
    print '<tr>';
	print '<td>'.$langs->trans("Member").'</td><td class="valeur" colspan="3">'.$adh->getNomUrl(1,0,'subscription').'</td>';
    print '</tr>';

    // Date start subscription
    print '<tr><td>'.$langs->trans("DateSubscription").'</td><td class="valeur" colspan="2">';
	$form->select_date($object->dateh,'datesub',1,1,0,'update',1);
	print '</td>';
    print '</tr>';

    // Date end subscription
    print '<tr><td>'.$langs->trans("DateEndSubscription").'</td><td class="valeur" colspan="2">';
	$form->select_date($object->datef,'datesubend',0,0,0,'update',1);
	print '</td>';
    print '</tr>';

    // Amount
    print '<tr><td>'.$langs->trans("Amount").'</td><td class="valeur" colspan="2">';
	print '<input type="text" class="flat" size="10" name="amount" value="'.price($object->amount).'"></td></tr>';

    // Label
    print '<tr><td>'.$langs->trans("Label").'</td><td class="valeur" colspan="2">';
	print '<input type="text" class="flat" size="60" name="note" value="'.$object->note.'"></td></tr>';

	// Bank line
	if (! empty($conf->banque->enabled))
	{
		if ($conf->global->ADHERENT_BANK_USE || $object->fk_bank)
		{
			print '<tr><td>'.$langs->trans("BankTransactionLine").'</td><td class="valeur" colspan="2">';
			if ($object->fk_bank)
			{
				$bankline=new AccountLine($db);
				$result=$bankline->fetch($object->fk_bank);
				print $bankline->getNomUrl(1,0,'showall');
			}
			else
			{
				print $langs->trans("NoneF");
			}
			print '</td></tr>';
		}
	}

	print '</table>';

	dol_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button" name="submit" value="'.$langs->trans("Save").'">';
	print ' &nbsp; &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';

	print '</form>';
	print "\n";
}

if ($rowid && $action != 'edit')
{
	/********************************************
	 *
	 * Subscription card in view mode
	 *
	 ********************************************/

    $result=$object->fetch($rowid);
	$result=$adh->fetch($object->fk_adherent);

	$head = subscription_prepare_head($object);

	dol_fiche_head($head, 'general', $langs->trans("Subscription"), -1, 'payment');

    // Confirmation to delete subscription
    if ($action == 'delete')
    {
		//$formquestion=array();
        //$formquestion['text']='<b>'.$langs->trans("ThisWillAlsoDeleteBankRecord").'</b>';
		$text=$langs->trans("ConfirmDeleteSubscription");
		if (! empty($conf->banque->enabled) && ! empty($conf->global->ADHERENT_BANK_USE)) $text.='<br>'.img_warning().' '.$langs->trans("ThisWillAlsoDeleteBankRecord");
		print $form->formconfirm($_SERVER["PHP_SELF"]."?rowid=".$object->id,$langs->trans("DeleteSubscription"),$text,"confirm_delete",$formquestion,0,1);
    }

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    $linkback = '<a href="'.dol_buildpath('/adherentsplus/subscription/list.php',1).'">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'rowid', $linkback, 1);

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';

    print '<table class="border" width="100%">';

    // Member
	$adh->ref=$adh->getFullName($langs);
    print '<tr>';
	print '<td class="titlefield">'.$langs->trans("Member").'</td><td class="valeur">'.$adh->getNomUrl(1,0,'subscription').'</td>';
    print '</tr>';

    // Date record
    /*print '<tr>';
	print '<td>'.$langs->trans("DateSubscription").'</td><td class="valeur">'.dol_print_date($object->datec,'dayhour').'</td>';
    print '</tr>';*/

    // Date subscription
    print '<tr>';
	print '<td>'.$langs->trans("DateSubscription").'</td><td class="valeur">'.dol_print_date($object->dateh,'day').'</td>';
    print '</tr>';

    // Date end subscription
    print '<tr>';
	print '<td>'.$langs->trans("DateEndSubscription").'</td><td class="valeur">'.dol_print_date($object->datef,'day').'</td>';
    print '</tr>';

    // Amount
    print '<tr><td>'.$langs->trans("Amount").'</td><td class="valeur">'.price($object->amount).'</td></tr>';

    // Amount
    print '<tr><td>'.$langs->trans("Label").'</td><td class="valeur">'.$object->note.'</td></tr>';

	// Bank line
	if (! empty($conf->banque->enabled))
	{
		if ($conf->global->ADHERENT_BANK_USE || $object->fk_bank)
		{
			print '<tr><td>'.$langs->trans("BankTransactionLine").'</td><td class="valeur">';
			if ($object->fk_bank)
			{
				$bankline=new AccountLine($db);
				$result=$bankline->fetch($object->fk_bank);
				print $bankline->getNomUrl(1,0,'showall');
			}
			else
			{
				print $langs->trans("NoneF");
			}
			print '</td></tr>';
		}
	}

    print "</table>\n";
    print '</div>';

    print '</form>';

    dol_fiche_end();

    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';

    if ($user->rights->adherent->cotisation->creer)
	{
		if (! $bankline->rappro)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"]."?rowid=".$object->id."&action=edit\">".$langs->trans("Modify")."</a></div>";
		}
		else
		{
			print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.$langs->trans("BankLineConciliated")."\" href=\"#\">".$langs->trans("Modify")."</a></div>";
		}
	}

    // Supprimer
    if ($user->rights->adherent->cotisation->creer)
    {
        print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"]."?rowid=".$object->id."&action=delete\">".$langs->trans("Delete")."</a></div>\n";
    }

    print '</div>';


    print '<div class="fichecenter"><div class="fichehalfleft">';
    print '<a name="builddoc"></a>'; // ancre

    // Documents generes
    /*
    $filename = dol_sanitizeFileName($object->ref);
    $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
    $urlsource = $_SERVER['PHP_SELF'] . '?facid=' . $object->id;
    $genallowed = $user->rights->facture->lire;
    $delallowed = $user->rights->facture->creer;

    print $formfile->showdocuments('facture', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);
    $somethingshown = $formfile->numoffiles;
    */
	// Show links to link elements
	//$linktoelem = $form->showLinkToObjectBlock($object, null, array('subscription'));
    $somethingshown = $form->showLinkedObjectBlock($object, '');

    // Show links to link elements
    /*$linktoelem = $form->showLinkToObjectBlock($object,array('order'));
	if ($linktoelem) print ($somethingshown?'':'<br>').$linktoelem;

    // Link for paypal payment
    /*
    if (! empty($conf->paypal->enabled) && $object->statut != 0) {
        include_once DOL_DOCUMENT_ROOT . '/paypal/lib/paypal.lib.php';
        print showPaypalPaymentUrl('invoice', $object->ref);
    }
    */
    print '</div><div class="fichehalfright"><div class="ficheaddleft">';

    // List of actions on element
    /*
    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
    $formactions = new FormActions($db);
    $somethingshown = $formactions->showactions($object, 'invoice', $socid);
    */

    print '</div></div></div>';
}


llxFooter();

$db->close();
