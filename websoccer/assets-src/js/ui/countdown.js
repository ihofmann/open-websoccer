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
 * Countdown timers for `.countdown` components.
 * The target date is read from the element's `data-date` attribute.
 */
export function initCountdowns() {
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
