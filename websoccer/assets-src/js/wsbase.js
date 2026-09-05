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

import { Tooltip, Popover } from "bootstrap";

(function () {
  "use strict";

  const WSCONFIG = {
    AJAX_URL: "ajax.php",
  };


  /* ------------------------------------------------------------------ */
  /* BlockUI replacement: lightweight overlay                            */
  /* ------------------------------------------------------------------ */
  function block(el) {
    if (!el) return;
    const overlay = document.createElement("div");
    overlay.className = "ws-block-overlay";
    if (getComputedStyle(el).position === "static") {
      el.style.position = "relative";
    }
    el.appendChild(overlay);
  }

  function unblock(el) {
    if (!el) return;
    el.querySelectorAll(".ws-block-overlay").forEach(function (o) {
      o.remove();
    });
  }

  /* ------------------------------------------------------------------ */
  /* Autocomplete (vanilla, AJAX)                                        */
  /* ------------------------------------------------------------------ */
  function initAutoComplete() {
    document.querySelectorAll(".autocomplete").forEach(function (input) {
      if (input.dataset.wsAcInit) return;
      input.dataset.wsAcInit = "1";

      const listbox = document.createElement("ul");
      listbox.className = "ws-autocomplete-list list-group";
      listbox.setAttribute("role", "listbox");
      input.parentNode.style.position = input.parentNode.style.position || "relative";
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

  /* ------------------------------------------------------------------ */
  /* Tooltips & popovers                                                */
  /* ------------------------------------------------------------------ */
  function initTooltips() {
    document.querySelectorAll(".wstooltip").forEach(function (el) {
      if (el.dataset.bsTooltipInit) return;
      el.dataset.bsTooltipInit = "1";
      try {
        new Tooltip(el);
      } catch (e) {
        console.error(e);
      }
    });
  }

  function initPopovers() {
    document.querySelectorAll(".wspopover").forEach(function (el) {
      if (el.dataset.bsPopoverInit) return;
      el.dataset.bsPopoverInit = "1";
      try {
        new Popover(el);
      } catch (e) {
        console.error(e);
      }
    });

    /* notifications popup with dynamic content */
    const notificationsLink = document.getElementById("notificationsLink");
    if (notificationsLink && !notificationsLink.dataset.bsPopoverInit) {
      notificationsLink.dataset.bsPopoverInit = "1";
      const wrapper = document.getElementById("notificationspopupwrapper");
      const contentHtml = wrapper ? wrapper.innerHTML : "";
      if (wrapper) wrapper.remove();
      try {
        new Popover(notificationsLink, {
          html: true,
          placement: "bottom",
          content: contentHtml,
        });
      } catch (e) {
        console.error(e);
      }
    }
  }

  /* ------------------------------------------------------------------ */
  /* Range input values                                                  */
  /* ------------------------------------------------------------------ */
  function initRangeInputs() {
    document.querySelectorAll('input[type="range"][aria-describedby]').forEach(function (input) {
      const output = document.getElementById(input.getAttribute("aria-describedby"));
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

  /* ------------------------------------------------------------------ */
  /* Countdown (vanilla)                                                */
  /* ------------------------------------------------------------------ */
  function initCountdowns() {
    document.querySelectorAll(".countdown").forEach(function (component) {
      if (component.dataset.wsCountdownInit) return;
      component.dataset.wsCountdownInit = "1";

      const target = new Date(component.dataset.date).getTime();
      if (Number.isNaN(target)) return;

      function pad(n) {
        return n < 10 ? "0" + n : "" + n;
      }

      let timer = null;
      function update() {
        const now = Date.now();
        const diff = target - now;
        if (diff <= 0) {
          component.style.display = "none";
          clearInterval(timer);
          return;
        }
        const seconds = Math.floor(diff / 1000) % 60;
        const minutes = Math.floor(diff / 60000) % 60;
        const hours = Math.floor(diff / 3600000) % 24;
        const days = Math.floor(diff / 86400000);
        setEl("seconds", pad(seconds));
        setEl("minutes", pad(minutes));
        setEl("hours", pad(hours));
        setEl("days", days);
        setEl("daysLeft", days);
      }

      function setEl(id, val) {
        const el = component.querySelector("#" + id);
        if (el) el.textContent = val;
      }

      update();
      if (target > Date.now()) {
        timer = setInterval(update, 1000);
      }
    });
  }

  /* ------------------------------------------------------------------ */
  /* AJAX helper                                                        */
  /* ------------------------------------------------------------------ */
  function ajaxHandler(
    queryString,
    targetId,
    blockId,
    messagesTargetId,
    blockedElement,
    ignoreemptymessages
  ) {
    if (!blockId) blockId = "";
    const requestUrl =
      WSCONFIG.AJAX_URL + "?block=" + encodeURIComponent(blockId) + "&" + queryString;
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
        initComponents();
        blockMatchRefreshButton();
        triggerAjaxLinksOnLoad();
        document.dispatchEvent(new CustomEvent("ws:ajaxComplete"));
      });
  }

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
  function triggerAjaxLinksOnLoad() {
    document.querySelectorAll(".triggerClickOnLoad").forEach(function (el) {
      if (!el.classList.contains("clicked")) {
        el.click();
        el.classList.add("clicked");
      }
    });
  }

  function triggerAjaxLoadOfBlocks() {
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
  function blockMatchRefreshButton() {
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

  /* ------------------------------------------------------------------ */
  /* Direct-transfer offer form: hide submit button on success          */
  /* (replaces an inline <script> that used jQuery $("#offerSubmit")    */
  /* .hide() — moved out so a strict CSP can be applied)                */
  /* ------------------------------------------------------------------ */
  function initDirectTransferOfferSuccess() {
    var offerFormBlock = document.getElementById("offerFormBlock");
    if (offerFormBlock && offerFormBlock.querySelector(".transfer-offer-success")) {
      var submitBtn = document.getElementById("offerSubmit");
      if (submitBtn) submitBtn.style.display = "none";
    }
  }

  /* ------------------------------------------------------------------ */
  /* Dynamic styles via data attributes (CSP-safe replacements for      */
  /* inline style="width: X%" and style="background-color: X")          */
  /* ------------------------------------------------------------------ */
  function initDynamicStyles() {
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

  /* ------------------------------------------------------------------ */
  /* Init components (also re-run after AJAX updates)                   */
  /* ------------------------------------------------------------------ */
  function initComponents() {
    initTooltips();
    initPopovers();
    initAutoComplete();
    initRangeInputs();
    initCountdowns();
    initDirectTransferOfferSuccess();
    initDynamicStyles();
  }

  document.addEventListener("DOMContentLoaded", function () {
    initComponents();
    triggerAjaxLinksOnLoad();
    triggerAjaxLoadOfBlocks();
    blockMatchRefreshButton();
  });
})();
