{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@173271 *}

<h1 class="page-header">{'LBL_MODIFY_PROFILE'|getTranslatedString}</h1>

<div class="row options" style="background-color: #ECF0F1">
	<div class="col-xs-12 col-sm-12 col-md-4 btn btn-default" style="cursor: pointer;margin: 0px;padding:0px" onClick="window.location.href='index.php?module=Contacts&amp;action=index&amp;id={$CUSTOMERID}&amp;profile=yes&amp;update=yes'">
		<center>
			<h4>{'UPDATE_PROFILE'|getTranslatedString}</h4>
		</center>
	</div>

	<div class="col-xs-12 col-sm-12 col-md-4 Unsubscribe btn btn-default" style="cursor: pointer;margin: 0px;padding:0px" onClick="if(confirm('{'MSG_CONF_UNSUBSCRIBE'|getTranslatedString}')){ldelim} window.location.href='index.php?module=Contacts&action=index&fun=unsubscribe&id={$CUSTOMERID}'; {rdelim}">
		<center>
			<h4 data-toggle="modal">{'LBL_UNSUBSCRIBE'|getTranslatedString}</h4>
		</center>
	</div>

	<div class="col-xs-12 col-sm-12 col-md-4 btn btn-default" style="cursor: pointer;margin: 0px;padding:0px">
		<center>
			<h4 data-toggle="modal" data-target=".bs-example-modal-sm">{'LBL_CHANGE_PASSWORD'|getTranslatedString}</h4>
		</center>

		<div class="modal fade bs-example-modal-sm" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  			<div class="modal-dialog modal-sm modal-xs">
    			<div class="modal-content">
     				<iframe class="embed-responsive-item changepw" src="MySettings.php"></iframe>
   				</div>
  			</div>
		</div>
	</div>
							
</div> 