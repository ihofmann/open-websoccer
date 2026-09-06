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

/** Initialise Bootstrap tooltips on `.wstooltip` elements. */
export function initTooltips() {
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

/** Initialise Bootstrap popovers on `.wspopover` elements. */
export function initPopovers() {
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
