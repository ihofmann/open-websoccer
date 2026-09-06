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
 * CSP-safe replacements for inline styles: progress-bar widths from
 * `data-width` and background colours from `data-bg-color` are applied via
 * the CSSOM instead of style attributes.
 */
export function initDynamicStyles() {
  /* Progress bar widths (Bootstrap progress-bar with data-width) */
  document.querySelectorAll(".progress-bar[data-width]").forEach(function (bar) {
    bar.style.width = bar.dataset.width + "%";
  });

  /* Table marker / legend background colours */
  document.querySelectorAll("[data-bg-color]").forEach(function (el) {
    var color = el.dataset.bgColor;
    if (color && color.charAt(0) !== "#") {
      color = "#" + color;
    }
    el.style.backgroundColor = color;
  });
}
