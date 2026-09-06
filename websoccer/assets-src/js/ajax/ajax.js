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

import { WSCONFIG } from "../core/config.js";
import { block, unblock } from "../ui/blockui.js";

/**
 * Core AJAX helper used by the AJAXified forms, links and auto-loaded blocks.
 * Fetches a JSON response for the given query string, replaces the target
 * element's content and dispatches a `ws:ajaxComplete` event when done, so
 * other modules can re-initialise their components.
 */
export function ajaxHandler(
  queryString,
  targetId,
  blockId,
  messagesTargetId,
  blockedElement,
  ignoreemptymessages
) {
  if (!blockId) blockId = "";

  // Append the CSRF token to every AJAX request so that state-changing
  // actions are protected against CSRF. The token is set by the layout
  // template (window.wsCsrfToken) and validated server-side.
  const csrfToken = window.wsCsrfToken || "";
  const csrfParam = csrfToken ? "&csrf_token=" + encodeURIComponent(csrfToken) : "";

  const requestUrl =
    WSCONFIG.AJAX_URL + "?block=" + encodeURIComponent(blockId) + "&" + queryString + csrfParam;
  const ajaxLoader = document.getElementById("ajaxLoaderPage");

  if (ajaxLoader) ajaxLoader.style.display = "block";
  block(blockedElement);

  fetch(requestUrl, { headers: { "X-Requested-With": "XMLHttpRequest" } })
    .then(function (r) {
      return r.json();
    })
    .then(function (data) {
      const target = document.getElementById(targetId);
      if (target && data.content) target.innerHTML = data.content;

      if (
        (!ignoreemptymessages || (data.messages && data.messages.trim().length)) &&
        data.messages
      ) {
        const msgTargetId = messagesTargetId || "messages";
        const mt = document.getElementById(msgTargetId);
        if (mt) mt.innerHTML = data.messages;
      }
    })
    .catch(function (err) {
      console.error(err);
    })
    .finally(function () {
      unblock(blockedElement);
      if (ajaxLoader) ajaxLoader.style.display = "none";
      document.dispatchEvent(new CustomEvent("ws:ajaxComplete"));
    });
}
