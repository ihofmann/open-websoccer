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

import { ajaxHandler } from "./ajax.js";

/* ------------------------------------------------------------------ */
/* AJAXified forms                                                    */
/* ------------------------------------------------------------------ */
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".ajaxSubmit");
  if (!btn) return;
  e.preventDefault();

  const form = btn.closest("form");
  if (!form) return;

  const params = new URLSearchParams(new FormData(form));
  ajaxHandler(
    params.toString(),
    btn.dataset.ajaxtarget,
    btn.dataset.ajaxblock,
    btn.dataset.messagetarget,
    form,
    btn.dataset.ignoreemptymessages
  );
});

/* ------------------------------------------------------------------ */
/* AJAXified links                                                    */
/* ------------------------------------------------------------------ */
document.addEventListener("click", function (e) {
  const link = e.target.closest(".ajaxLink");
  if (!link) return;
  e.preventDefault();

  const targetId = link.dataset.ajaxtarget;
  if (!link.dataset.ajaxloaded || link.dataset.ajaxdisabledcache) {
    const blockedElement = document.getElementById(targetId);
    ajaxHandler(
      link.dataset.ajaxquerystr,
      targetId,
      link.dataset.ajaxblock,
      link.dataset.messagetarget,
      blockedElement ? blockedElement.closest("div") : null,
      link.dataset.ignoreemptymessages
    );

    /* cache only if area is not updated by any other link */
    if (
      document.querySelectorAll('a[data-ajaxtarget="' + targetId + '"]').length < 2
    ) {
      link.dataset.ajaxloaded = "1";
    }
  }
});

/* enable browser history after AJAX link */
document.addEventListener("DOMContentLoaded", function () {
  const hash = location.hash.replace("#", "");
  if (hash.length > 0) {
    const link = document.querySelector('a.ajaxLink[href$="#' + hash + '"]');
    if (link) link.click();
  }
});

/* enable browser history for tab panes */
document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function (a) {
  a.addEventListener("shown.bs.tab", function (e) {
    window.location.hash = e.target.hash;
  });
});

/* client side "active" marker of nav items */
document.addEventListener("click", function (e) {
  const item = e.target.closest(".clientsideNavItem");
  if (!item) return;
  const nav = item.closest(".nav");
  if (nav) {
    nav
      .querySelectorAll(".clientsideNavItem.active")
      .forEach(function (el) {
        el.classList.remove("active");
      });
    item.classList.add("active");
  }
});

/* ------------------------------------------------------------------ */
/* Auto-trigger links / blocks on load                                */
/* ------------------------------------------------------------------ */
export function triggerAjaxLinksOnLoad() {
  document.querySelectorAll(".triggerClickOnLoad").forEach(function (el) {
    if (!el.classList.contains("clicked")) {
      el.click();
      el.classList.add("clicked");
    }
  });
}

export function triggerAjaxLoadOfBlocks() {
  document.querySelectorAll(".ajaxLoadedBlock").forEach(function (el) {
    const queryStr = el.dataset.ajaxquerystr;
    const elementId = el.id;
    const blockId = el.dataset.ajaxblock;
    const messagesTarget = el.dataset.messagetarget;
    const ignoreEmptyMessages = el.dataset.ignoreemptymessages;
    const refreshPeriod = el.dataset.refreshperiod;

    ajaxHandler(queryStr, elementId, blockId, messagesTarget, el, ignoreEmptyMessages);

    if (refreshPeriod) {
      setInterval(function () {
        ajaxHandler(
          queryStr,
          elementId,
          blockId,
          messagesTarget,
          el,
          ignoreEmptyMessages
        );
      }, refreshPeriod * 1000);
    }
  });
}

/* ------------------------------------------------------------------ */
/* Block AJAX refresh button at match reports                         */
/* ------------------------------------------------------------------ */
let refreshCountdownStarted = false;
export function blockMatchRefreshButton() {
  document.querySelectorAll("#matchReportRefresh").forEach(function (btn) {
    let timeToBlock = parseInt(btn.dataset.blockseconds, 10);
    if (Number.isNaN(timeToBlock)) return;

    if (!refreshCountdownStarted) {
      btn.setAttribute("disabled", "disabled");
      const countdownElement = btn.querySelector(".timerCount");
      const interval = setInterval(function () {
        refreshCountdownStarted = true;
        timeToBlock--;
        if (countdownElement) countdownElement.textContent = "(" + timeToBlock + ")";
        if (timeToBlock === 0) {
          btn.removeAttribute("disabled");
          if (countdownElement) countdownElement.textContent = "";
          refreshCountdownStarted = false;
          clearInterval(interval);
          /* automatic refresh */
          btn.click();
        }
      }, 1000);
    }
  });
}
