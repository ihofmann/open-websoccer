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
 * Mirror range input values into their `aria-describedby` output elements.
 */
export function initRangeInputs() {
  document
    .querySelectorAll('input[type="range"][aria-describedby]')
    .forEach(function (input) {
      const output = document.getElementById(
        input.getAttribute("aria-describedby")
      );
      if (!output || output.dataset.wsRangeInit) return;
      output.dataset.wsRangeInit = "1";

      function update() {
        const unit = output.dataset.unit || "";
        output.textContent = input.value + (unit ? " " + unit : "");
      }

      input.addEventListener("input", update);
      input.addEventListener("change", update);
      update();
    });
}
