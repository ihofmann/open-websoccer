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

import { Modal } from "bootstrap";

(function () {
  "use strict";

  function qs(sel, ctx) {
    return (ctx || document).querySelector(sel);
  }
  function qsa(sel, ctx) {
    return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
  }

  /* expose selectAll for legacy onclick handlers */
  window.selectAll = function () {
    var c = document.frmMain.selAll.checked;
    for (var i = 0; i < document.frmMain.elements.length; i++) {
      var el = document.frmMain.elements[i];
      if (el.type == "checkbox" && el.name != document.frmMain.selAll.name) {
        el.checked = c;
      }
    }
  };

  /* ------------------------------------------------------------------ */
  /* wsConfirm: bootbox.confirm replacement using a shared BS5 modal     */
  /* ------------------------------------------------------------------ */
  function ensureConfirmModal() {
    var modal = document.getElementById("wsConfirmModal");
    if (modal) return modal;

    modal = document.createElement("div");
    modal.id = "wsConfirmModal";
    modal.className = "modal fade";
    modal.setAttribute("tabindex", "-1");
    modal.setAttribute("aria-hidden", "true");
    modal.innerHTML =
      '<div class="modal-dialog modal-dialog-centered">' +
      '<div class="modal-content">' +
      '<div class="modal-body" id="wsConfirmMessage"></div>' +
      '<div class="modal-footer">' +
      '<button type="button" class="btn btn-secondary" id="wsConfirmNo"></button>' +
      '<button type="button" class="btn btn-primary" id="wsConfirmYes"></button>' +
      "</div></div></div>";
    document.body.appendChild(modal);
    return modal;
  }

  window.wsConfirm = function (message, noLabel, yesLabel, callback) {
    var modalEl = ensureConfirmModal();
    qs("#wsConfirmMessage", modalEl).textContent = message;
    var noBtn = qs("#wsConfirmNo", modalEl);
    var yesBtn = qs("#wsConfirmYes", modalEl);
    noBtn.textContent = noLabel || "Cancel";
    yesBtn.textContent = yesLabel || "OK";

    var instance = new Modal(modalEl);

    function cleanup() {
      noBtn.removeEventListener("click", onNo);
      yesBtn.removeEventListener("click", onYes);
      modalEl.removeEventListener("hidden.bs.modal", onHidden);
    }
    function onNo() {
      instance.hide();
      cleanup();
      if (callback) callback(false);
    }
    function onYes() {
      instance.hide();
      cleanup();
      if (callback) callback(true);
    }
    function onHidden() {
      cleanup();
    }
    noBtn.addEventListener("click", onNo);
    yesBtn.addEventListener("click", onYes);
    modalEl.addEventListener("hidden.bs.modal", onHidden);

    instance.show();
  };

  /* ------------------------------------------------------------------ */
  /* Primary key picker (vanilla AJAX autocomplete)                      */
  /* ------------------------------------------------------------------ */
  function initPkPickers() {
    qsa(".pkpicker").forEach(function (hidden) {
      if (hidden.dataset.wsPkInit) return;
      hidden.dataset.wsPkInit = "1";

      var wrapper = hidden.parentElement;
      var display = wrapper.querySelector(".pkpicker-display");
      var list = wrapper.querySelector(".pkpicker-list");
      if (!display || !list) return;

      var dbtable = hidden.dataset.dbtable;
      var labelcolumns = hidden.dataset.labelcolumns;
      var debounce = null;

      function close() {
        list.innerHTML = "";
        list.style.display = "none";
      }

      function fetchLabel() {
        if (!hidden.value || hidden.value === "0") return;
        var url =
          "itemsprovider.php?dbtable=" +
          encodeURIComponent(dbtable) +
          "&labelcolumns=" +
          encodeURIComponent(labelcolumns) +
          "&itemid=" +
          encodeURIComponent(hidden.value);
        fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } })
          .then(function (r) {
            return r.json();
          })
          .then(function (data) {
            if (data && data[0]) display.value = data[0].text;
          })
          .catch(function () {});
      }

      function search(query) {
        var url =
          "itemsprovider.php?dbtable=" +
          encodeURIComponent(dbtable) +
          "&labelcolumns=" +
          encodeURIComponent(labelcolumns) +
          "&search=" +
          encodeURIComponent(query);
        fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } })
          .then(function (r) {
            return r.json();
          })
          .then(function (items) {
            render(items);
          })
          .catch(function () {
            close();
          });
      }

      function render(items) {
        list.innerHTML = "";
        if (!items || !items.length) {
          close();
          return;
        }
        items.forEach(function (item) {
          var li = document.createElement("li");
          li.className = "list-group-item list-group-item-action";
          li.setAttribute("role", "option");
          li.textContent = item.text;
          li.addEventListener("mousedown", function (e) {
            e.preventDefault();
            hidden.value = item.id;
            display.value = item.text;
            close();
          });
          list.appendChild(li);
        });
        list.style.display = "block";
      }

      display.addEventListener("input", function () {
        var query = display.value;
        if (query.length < 2) {
          close();
          return;
        }
        clearTimeout(debounce);
        debounce = setTimeout(function () {
          search(query);
        }, 200);
      });

      display.addEventListener("blur", function () {
        setTimeout(close, 150);
      });

      display.addEventListener("focus", function () {
        if (display.value && !hidden.value) {
          /* nothing preloaded yet */
        }
      });

      fetchLabel();
    });
  }

  /* ------------------------------------------------------------------ */
  /* Init                                                                */
  /* ------------------------------------------------------------------ */
  document.addEventListener("DOMContentLoaded", function () {
    initPkPickers();

    /* start / stop (cron) job */
    qsa(".startStopJobLink").forEach(function (link) {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        var spinner = document.getElementById("ajaxSpinner");
        if (spinner) spinner.style.display = "block";
        fetch(link.getAttribute("href"), {
          headers: { "X-Requested-With": "XMLHttpRequest" },
        }).finally(function () {
          if (spinner) spinner.style.display = "none";
          location.reload();
        });
      });
    });

    /* enable table row multiple selection */
    qsa(".tableRowSelectionCell").forEach(function (cell) {
      cell.addEventListener("click", function () {
        var cb = cell.parentElement.querySelector("input[type=checkbox]");
        if (cb) cb.click();
      });
    });

    /* select teams for cup match creation */
    function teamForCupChangeHandler() {
      var noOfTeams = qsa(".teamForCupCheckbox:checked").length;
      var noOfRounds = Math.log2(noOfTeams);

      var countEl = document.getElementById("numberOfTeamsSelected");
      if (countEl) countEl.textContent = noOfTeams;

      var noAlert = document.getElementById("noCupPossibleAlert");
      var okAlert = document.getElementById("possibleCupRoundsAlert");

      /* 0 rounds or not a natural number */
      if (noOfRounds === 0 || !/^(0|([1-9]\d*))$/.test(noOfRounds)) {
        if (noAlert) noAlert.style.display = "block";
        if (okAlert) okAlert.style.display = "none";
        return;
      }

      if (okAlert) okAlert.style.display = "block";
      if (noAlert) noAlert.style.display = "none";

      var roundsEl = document.getElementById("roundsNo");
      if (roundsEl) roundsEl.textContent = Math.round(noOfRounds);
    }

    qsa(".teamForCupCheckbox").forEach(function (cb) {
      cb.addEventListener("change", teamForCupChangeHandler);
    });
  });
})();
