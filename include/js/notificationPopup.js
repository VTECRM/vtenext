/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/**
 * this function takes a node and displays it as a popup in the right-hand side corner of the window
 *
 * @param node - the node to display as popup
 */

// crmv@164368

function _defPopup() {
	var node;
    var remainOnScreen = 10 * 1000; // the time for which the popup remains on screen
    var randomID = Math.floor(Math.random() * 10001);

    var parentDiv = document.getElementById('notificationDiv');
   
    var panel = document.createElement('div');
    panel.id = randomID;
    panel.className = "panel";
    panel.style.float = "right";
    panel.style.overflow = "hidden";
    panel.style.zIndex = 10;
    panel.align = "left"; // the popup to be displayed on screen
    
    var popupDiv = document.createElement('div');
    popupDiv.className = "panel-body";
    panel.appendChild(popupDiv);
    
    parentDiv.appendChild(panel);

    /**
     * this function creates a popup div and displays in on the screen
     * after a timeinterval of time seconds the popup is hidden
     *
     * @param node - the node to display
     * @param height - the maximum height of the popup
     * @param time - the time for which it is displayed
     */
    function CreatePopup(node, time) {
        jQuery(panel).hide();
        jQuery(parentDiv).show();

        if (time != undefined && time != "") {
            remainOnScreen = time * 1000;
        }

        popupDiv.innerHTML = node;

        ShowPopup();
    }

    /**
     * this function is used to display the popup on screen
     */
    function ShowPopup() {
        jQuery(panel).fadeIn();
        setTimeout(HidePopup, remainOnScreen);
    }

    /**
     * this function is used to hide the popup from screen
     */
    function HidePopup() {
        jQuery(panel).fadeOut();
        ResetPopup();
    }

    /**
     * this function is used to reset the popup
     */
    function ResetPopup() {
    	jQuery(panel).html('');
        jQuery(parentDiv).hide();
    }

    return {
        displayPopup: CreatePopup,
        content: node,
    };
}