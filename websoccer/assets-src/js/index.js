/******************************************************

  This file is part of OpenWebSoccer-Sim.

  OpenWebSoccer-Sim is free software: you can redistribute it
  and/or modify it under the terms of the
  GNU Lesser General Public License
  as published by the Free Software Foundation, either version 3 of
  the License, or any later version.

  OpenWebSoccer-Sim is distributed in the hope that it will be
  useful, but WITHOUT ANY WARRANTY; without even the implied
  warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the GNU Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public
  License along with OpenWebSoccer-Sim.
  If not, see <http://www.gnu.org/licenses/>.

******************************************************/

import { initTooltips, initPopovers } from "./ui/tooltips.js";
import { initAutoComplete } from "./ui/autocomplete.js";
import { initRangeInputs } from "./ui/rangeinputs.js";
import { initCountdowns } from "./ui/countdown.js";
import { initDirectTransferOfferSuccess } from "./ui/directtransfer.js";
import { initDynamicStyles } from "./ui/dynamicstyles.js";
import { initSelectFormSubmit } from "./ui/selectformsubmit.js";
import {
  triggerAjaxLinksOnLoad,
  triggerAjaxLoadOfBlocks,
  blockMatchRefreshButton,
} from "./ajax/ajaxify.js";

/**
 * Default skin bootstrapping.
 *
 * Initialises all UI components on page load and re-initialises them after
 * every AJAX content update (see the `ws:ajaxComplete` event dispatched by
 * the AJAX helper).
 */
function initComponents() {
  initTooltips();
  initPopovers();
  initAutoComplete();
  initRangeInputs();
  initCountdowns();
  initDirectTransferOfferSuccess();
  initDynamicStyles();
  initSelectFormSubmit();
}

document.addEventListener("DOMContentLoaded", function () {
  initComponents();
  triggerAjaxLinksOnLoad();
  triggerAjaxLoadOfBlocks();
  blockMatchRefreshButton();
});

document.addEventListener("ws:ajaxComplete", function () {
  initComponents();
  blockMatchRefreshButton();
  triggerAjaxLinksOnLoad();
});
