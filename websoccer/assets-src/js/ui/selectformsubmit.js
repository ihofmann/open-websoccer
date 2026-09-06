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
 * Auto-submit forms when a view-update select changes.
 * Selects with class `select-form-submit` apply their selection immediately
 * instead of requiring an "apply" button click.
 */
export function initSelectFormSubmit() {
  document
    .querySelectorAll("select.select-form-submit")
    .forEach(function (select) {
      if (select.dataset.wsSelectSubmitInit) return;
      select.dataset.wsSelectSubmitInit = "1";
      select.addEventListener("change", function () {
        const form = select.closest("form");
        if (!form) return;
        /* Reuse the existing AJAX submit handler for AJAXified forms,
           otherwise submit the form natively. */
        const ajaxBtn = form.querySelector(".ajaxSubmit");
        if (ajaxBtn) {
          ajaxBtn.click();
        } else {
          form.submit();
        }
      });
    });
}
