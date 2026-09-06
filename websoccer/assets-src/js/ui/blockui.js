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
 * Lightweight blockUI replacement: an overlay that covers an element while
 * an AJAX request is in flight.
 */

/** Cover `el` with a blocking overlay. */
export function block(el) {
  if (!el) return;
  const overlay = document.createElement("div");
  overlay.className = "ws-block-overlay";
  if (getComputedStyle(el).position === "static") {
    el.style.position = "relative";
  }
  el.appendChild(overlay);
}

/** Remove any blocking overlay from `el`. */
export function unblock(el) {
  if (!el) return;
  el.querySelectorAll(".ws-block-overlay").forEach(function (o) {
    o.remove();
  });
}
