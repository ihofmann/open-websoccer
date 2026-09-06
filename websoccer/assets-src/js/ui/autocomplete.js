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

/**
 * Vanilla AJAX autocomplete for `.autocomplete` inputs.
 * Suggestions are fetched from the server as JSON and rendered as a listbox.
 */
export function initAutoComplete() {
  document.querySelectorAll(".autocomplete").forEach(function (input) {
    if (input.dataset.wsAcInit) return;
    input.dataset.wsAcInit = "1";

    const listbox = document.createElement("ul");
    listbox.className = "ws-autocomplete-list list-group";
    listbox.setAttribute("role", "listbox");
    input.parentNode.style.position =
      input.parentNode.style.position || "relative";
    input.parentNode.appendChild(listbox);

    let debounce = null;

    function close() {
      listbox.innerHTML = "";
      listbox.style.display = "none";
    }

    function render(options) {
      listbox.innerHTML = "";
      if (!options || !options.length) {
        close();
        return;
      }
      options.forEach(function (opt) {
        const value = typeof opt === "object" ? opt.value : opt;
        const label = typeof opt === "object" ? opt.label : opt;
        const item = document.createElement("li");
        item.className = "list-group-item list-group-item-action";
        item.setAttribute("role", "option");
        item.textContent = label;
        item.addEventListener("mousedown", function (e) {
          e.preventDefault();
          input.value = value;
          close();
        });
        listbox.appendChild(item);
      });
      listbox.style.display = "block";
    }

    input.addEventListener("input", function () {
      const query = input.value;
      if (query.length < 2) {
        close();
        return;
      }
      clearTimeout(debounce);
      debounce = setTimeout(function () {
        const block = input.dataset.ajaxblock;
        const url =
          WSCONFIG.AJAX_URL +
          "?contentonly=1&block=" +
          encodeURIComponent(block) +
          "&query=" +
          encodeURIComponent(query);
        fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } })
          .then(function (r) {
            return r.json();
          })
          .then(function (json) {
            if (json && json.options) {
              render(json.options);
            } else {
              close();
            }
          })
          .catch(function (err) {
            console.error(err);
            close();
          });
      }, 200);
    });

    input.addEventListener("blur", function () {
      setTimeout(close, 150);
    });
  });
}
