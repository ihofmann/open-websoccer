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
 * Shared DOM helpers used across the default skin's modules.
 */

/** First element matching `sel`, scoped to `ctx` (default: document). */
export function qs(sel, ctx) {
  return (ctx || document).querySelector(sel);
}

/** All elements matching `sel`, scoped to `ctx` (default: document), as an array. */
export function qsa(sel, ctx) {
  return Array.from((ctx || document).querySelectorAll(sel));
}

/** Parse a numeric data attribute as an integer (0 when missing or invalid). */
export function dataInt(el, key) {
  return parseInt(el.dataset[key], 10) || 0;
}

/** Parse a numeric data attribute as a float (0 when missing or invalid). */
export function dataFloat(el, key) {
  return parseFloat(el.dataset[key]) || 0;
}

/** Hide an element via the shared `ws-hidden` class. */
export function hide(el) {
  if (el) el.classList.add("ws-hidden");
}

/** Show an element by removing the shared `ws-hidden` class. */
export function show(el) {
  if (el) el.classList.remove("ws-hidden");
}

/** Escape a string for safe insertion into HTML. */
export function escapeHtml(s) {
  return String(s)
    .replace(/&/g, "&amp;")
    .replace(/"/g, "&quot;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}
