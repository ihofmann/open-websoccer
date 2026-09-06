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

/**
 * Direct-transfer offer form: hide the submit button when the offer has
 * already been accepted (replaces an inline <script> that used jQuery
 * `$("#offerSubmit").hide()` — moved out so a strict CSP can be applied).
 */
export function initDirectTransferOfferSuccess() {
  var offerFormBlock = document.getElementById("offerFormBlock");
  if (offerFormBlock && offerFormBlock.querySelector(".transfer-offer-success")) {
    var submitBtn = document.getElementById("offerSubmit");
    if (submitBtn) submitBtn.style.display = "none";
  }
}
