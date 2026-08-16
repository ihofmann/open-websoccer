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

(function () {
  "use strict";

  var CSS_CONFIG = {
    PITCH_POSITION: "position",
    PITCH_POSITION_LABEL: "positionLabel",
    PITCH_FREE_POSITION: "freePosition",
    PITCH_PLAYER_NAME: "positionPlayer",
    PITCH_PLAYER_REMOVE_LINK: "positionPlayerRemove",
    PITCH_PLAYER_STRENGTHBAR: "playerinfoStrength",
    PITCH_POS_STATE_PRIMARY: "positionStatePrimary",
    PITCH_POS_STATE_SECONDARY: "positionStateSecondary",
    PITCH_POS_STATE_WRONG: "positionStateWrong",
    ACTIONLINK_REMOVE: "playerRemoveLink",
    ACTIONLINK_ADD_TO_PITCH: "playerAddToPitchLink",
    ACTIONLINK_ADD_TO_PITCH_ITEM: "playerAddToPitchLinkItem",
    ACTIONLINK_ADD_TO_BENCH: "playerAddToBenchLink",
    PLAYER_ON_PITCH: "playerIsOnPitch",
    PLAYER_INFO: "playerinfo",
    PLAYER_DRAGGABLE: "playerDraggable",
    PLAYER_ON_BENCH: "playerIsOnBench",
    BENCH_POSITION: "benchposition",
    BENCH_PLAYER_REMOVE_LINK: "benchPlayerRemove",
    BENCH_PLAYER_SUB_LINK: "benchPlayerSubAdd",
    BENCH_ACTIVE_SUBSTITUTION: "benchActiveSubstitution",
  };

  /* ---------- small helpers ---------- */
  function qs(sel, ctx) {
    return (ctx || document).querySelector(sel);
  }
  function qsa(sel, ctx) {
    return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
  }
  function dataInt(el, key) {
    return parseInt(el.dataset[key], 10) || 0;
  }
  function dataFloat(el, key) {
    return parseFloat(el.dataset[key]) || 0;
  }
  function hide(el) {
    if (el) el.style.display = "none";
  }
  function show(el) {
    if (el) el.style.display = "";
  }
  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  var draggedPlayerId = null;

  /* ---------- core actions ---------- */
  function addPlayerToPitch(playerId, targetPos) {
    var player = qs("#playerinfo" + playerId);
    if (!player) return false;

    var targetPosition = qs(
      ".position." + targetPos + "." + CSS_CONFIG.PITCH_FREE_POSITION
    );
    var playerToSwap = 0;

    /* is position already occupied? */
    if (!targetPosition) {
      targetPosition = qs(".position." + targetPos);
      if (!targetPosition) return false;

      /* it is occupied. prepare player to swap if player-to-add is already on pitch */
      if (player.classList.contains(CSS_CONFIG.PLAYER_ON_PITCH)) {
        playerToSwap = dataInt(targetPosition, "playerid");
      } else {
        return false;
      }
    }

    /* do not add if player is on bench */
    if (player.classList.contains(CSS_CONFIG.PLAYER_ON_BENCH)) {
      return false;
    }

    /* if player has been already on pitch, remove him (move to new position) */
    if (player.classList.contains(CSS_CONFIG.PLAYER_ON_PITCH)) {
      var originalPosition = null;
      if (playerToSwap > 0) {
        var pos = qsa("." + CSS_CONFIG.PITCH_POSITION).find(function (el) {
          return String(el.dataset.playerid) === String(playerId);
        });
        originalPosition = pos ? pos.dataset.mainposition : null;
      }

      removePlayerFromPitch(playerId);

      if (playerToSwap > 0) {
        removePlayerFromPitch(playerToSwap);
        addPlayerToPitch(playerToSwap, originalPosition);
      }
    }

    /* do not add if blocked or injured */
    if (dataInt(player, "matchesblocked") > 0) {
      return false;
    }

    /* check position state */
    var playerStrength = dataFloat(player, "strength");
    var mainPos = player.dataset.mainposition;
    var secondPos = player.dataset.secondposition;
    var playerPos = player.dataset.position;

    if (
      (mainPos && targetPosition.classList.contains(mainPos)) ||
      (!mainPos && targetPosition.dataset.position === playerPos)
    ) {
      targetPosition.classList.add(CSS_CONFIG.PITCH_POS_STATE_PRIMARY);
    } else if (
      (secondPos && targetPosition.classList.contains(secondPos)) ||
      targetPosition.dataset.position === playerPos
    ) {
      targetPosition.classList.add(CSS_CONFIG.PITCH_POS_STATE_SECONDARY);
      playerStrength = dataFloat(player, "strengthsecondary");
    } else {
      targetPosition.classList.add(CSS_CONFIG.PITCH_POS_STATE_WRONG);
      playerStrength = dataFloat(player, "strengthwrong");
    }

    /* add ID */
    targetPosition.dataset.playerid = playerId;

    /* add strength bar */
    if (typeof playerStrength !== "undefined" && !isNaN(playerStrength)) {
      var progress_status = "danger";
      if (playerStrength > 80) progress_status = "success";
      else if (playerStrength > 50) progress_status = "info";
      else if (playerStrength > 30) progress_status = "warning";
      targetPosition.insertAdjacentHTML(
        "beforeend",
        '<div class="progress ' +
          CSS_CONFIG.PITCH_PLAYER_STRENGTHBAR +
          '"><div class="progress-bar bg-' +
          progress_status +
          '" style="width: ' +
          playerStrength +
          '%">' +
          playerStrength +
          "%</div></div>"
      );
    }

    /* add picture */
    if (player.dataset.picture) {
      targetPosition.insertAdjacentHTML(
        "beforeend",
        '<div class="formationPlayerPicture"><img src="' +
          escapeHtml(player.dataset.picture) +
          '"/></div>'
      );
      hide(qs(".positionLabel", targetPosition));
    } else {
      targetPosition.classList.add("jersey");
    }

    /* add name div */
    targetPosition.insertAdjacentHTML(
      "beforeend",
      '<div class="' +
        CSS_CONFIG.PITCH_PLAYER_NAME +
        '">' +
        escapeHtml(player.dataset.pname) +
        "</div>"
    );

    /* add remove icon */
    targetPosition.insertAdjacentHTML(
      "beforeend",
      '<a class="' +
        CSS_CONFIG.PITCH_PLAYER_REMOVE_LINK +
        '" href="#"><i class="bi bi-x-lg darkIcon"></i></a>'
    );

    /* mark as on pitch */
    player.classList.add(CSS_CONFIG.PLAYER_ON_PITCH);

    /* hide and show action links */
    qsa("." + CSS_CONFIG.ACTIONLINK_REMOVE, player).forEach(function (el) {
      el.style.display = "inline-block";
    });
    hide(qs("." + CSS_CONFIG.ACTIONLINK_ADD_TO_PITCH, player));
    hide(qs("." + CSS_CONFIG.ACTIONLINK_ADD_TO_BENCH, player));

    targetPosition.classList.remove(CSS_CONFIG.PITCH_FREE_POSITION);

    /* enable dragging the player away from this position */
    targetPosition.draggable = true;
    targetPosition.setAttribute("draggable", "true");

    /* add to hidden input field */
    var playerField = qs('.playerField[value="' + playerId + '"]');
    if (!playerField) {
      playerField = qs(".playerField[value='']");
    }
    if (playerField) {
      playerField.value = playerId;
      var posField = playerField.nextElementSibling;
      if (posField) posField.value = targetPos.substring(0, 2);
    }

    /* add to selection for substitutions */
    qsa(".playersOutSelection").forEach(function (sel) {
      sel.insertAdjacentHTML(
        "beforeend",
        '<option value="' +
          playerId +
          '">' +
          escapeHtml(player.dataset.pname) +
          "</option>"
      );
    });

    /* add to free kick taker selection */
    var fk = qs("#freekickplayer");
    if (fk) {
      fk.insertAdjacentHTML(
        "beforeend",
        '<option value="' +
          playerId +
          '">' +
          escapeHtml(player.dataset.pname) +
          "</option>"
      );
    }

    return true;
  }

  function removePlayerFromPitch(playerId) {
    var positionDiv = qsa("." + CSS_CONFIG.PITCH_POSITION).find(function (el) {
      return String(el.dataset.playerid) === String(playerId);
    });
    if (!positionDiv) return;

    delete positionDiv.dataset.playerid;
    qsa("." + CSS_CONFIG.PITCH_PLAYER_REMOVE_LINK, positionDiv).forEach(function (
      el
    ) {
      el.remove();
    });
    qsa("." + CSS_CONFIG.PITCH_PLAYER_NAME, positionDiv).forEach(function (el) {
      el.remove();
    });
    qsa("." + CSS_CONFIG.PITCH_PLAYER_STRENGTHBAR, positionDiv).forEach(function (
      el
    ) {
      el.remove();
    });

    positionDiv.classList.remove(CSS_CONFIG.PITCH_POS_STATE_PRIMARY);
    positionDiv.classList.remove(CSS_CONFIG.PITCH_POS_STATE_SECONDARY);
    positionDiv.classList.remove(CSS_CONFIG.PITCH_POS_STATE_WRONG);

    positionDiv.classList.add(CSS_CONFIG.PITCH_FREE_POSITION);
    positionDiv.removeAttribute("draggable");
    positionDiv.draggable = false;

    var player = qs("#playerinfo" + playerId);
    if (player) {
      player.classList.remove(CSS_CONFIG.PLAYER_ON_PITCH);
      qsa("." + CSS_CONFIG.ACTIONLINK_REMOVE, player).forEach(hide);
      show(qs("." + CSS_CONFIG.ACTIONLINK_ADD_TO_PITCH, player));
      show(qs("." + CSS_CONFIG.ACTIONLINK_ADD_TO_BENCH, player));
    }

    /* remove picture */
    if (player && player.dataset.picture) {
      qsa(".formationPlayerPicture", positionDiv).forEach(function (el) {
        el.remove();
      });
      show(qs(".positionLabel", positionDiv));
    } else {
      positionDiv.classList.remove("jersey");
    }

    /* remove from substitution selections */
    qsa('.playersOutSelection > option[value="' + playerId + '"]').forEach(
      function (opt) {
        opt.remove();
      }
    );

    /* remove from free kick taker selection */
    qsa('#freekickplayer > option[value="' + playerId + '"]').forEach(function (
      opt
    ) {
      opt.remove();
    });

    /* remove from hidden input field */
    var playerField = qs('.playerField[value="' + playerId + '"]');
    if (playerField) {
      playerField.value = "";
      var posField = playerField.nextElementSibling;
      if (posField) posField.value = "";
    }
  }

  function addPlayerToBench(playerId) {
    var player = qs("#playerinfo" + playerId);
    var targetPosition = qs(
      "." +
        CSS_CONFIG.BENCH_POSITION +
        "." +
        CSS_CONFIG.PITCH_FREE_POSITION
    );
    if (!player || !targetPosition) return false;

    if (
      player.classList.contains(CSS_CONFIG.PLAYER_ON_PITCH) ||
      player.classList.contains(CSS_CONFIG.PLAYER_ON_BENCH)
    ) {
      return false;
    }

    if (dataInt(player, "matchesblocked") > 0) return false;

    player.classList.add(CSS_CONFIG.PLAYER_ON_BENCH);
    targetPosition.dataset.playerid = playerId;

    var playerInfoCell = qs(" > .benchPlayerInfo", targetPosition);
    if (playerInfoCell) {
      hide(qs(" > .benchPlaceholder", playerInfoCell));

      var playerLabel = player.dataset.pname;
      if (player.dataset.mainposition) {
        playerLabel =
          playerLabel +
          " (" +
          (qs(".mainposition", player)
            ? qs(".mainposition", player).textContent
            : "");
        if (player.dataset.secondposition) {
          playerLabel =
            playerLabel +
            " / " +
            (qs(".secondposition", player)
              ? qs(".secondposition", player).textContent
              : "");
        }
        playerLabel = playerLabel + ")";
      }

      playerInfoCell.insertAdjacentHTML(
        "beforeend",
        '<span class="benchPlayer">' + escapeHtml(playerLabel) + "</span>"
      );
    }

    qsa("." + CSS_CONFIG.ACTIONLINK_REMOVE, player).forEach(function (el) {
      el.style.display = "inline-block";
    });
    hide(qs("." + CSS_CONFIG.ACTIONLINK_ADD_TO_PITCH, player));
    hide(qs("." + CSS_CONFIG.ACTIONLINK_ADD_TO_BENCH, player));

    show(qs("." + CSS_CONFIG.BENCH_PLAYER_REMOVE_LINK, targetPosition));

    if (qsa("." + CSS_CONFIG.BENCH_ACTIVE_SUBSTITUTION).length < 3) {
      show(qs("." + CSS_CONFIG.BENCH_PLAYER_SUB_LINK, targetPosition));
    }

    targetPosition.classList.remove(CSS_CONFIG.PITCH_FREE_POSITION);

    var playerIndex =
      Array.prototype.indexOf.call(
        targetPosition.parentElement.children,
        targetPosition
      ) + 1;
    var benchField = qs("#bench" + playerIndex);
    if (benchField) benchField.value = playerId;

    return true;
  }

  function removePlayerFromBench(playerId) {
    var positionDiv = qsa("." + CSS_CONFIG.BENCH_POSITION).find(function (el) {
      return String(el.dataset.playerid) === String(playerId);
    });
    if (!positionDiv) return;

    removeSubstitution(positionDiv);

    delete positionDiv.dataset.playerid;
    positionDiv.classList.add(CSS_CONFIG.PITCH_FREE_POSITION);

    qsa(".benchPlayer", positionDiv).forEach(function (el) {
      el.remove();
    });
    show(qs(".benchPlaceholder", positionDiv));

    var player = qs("#playerinfo" + playerId);
    if (player) {
      player.classList.remove(CSS_CONFIG.PLAYER_ON_BENCH);
      qsa("." + CSS_CONFIG.ACTIONLINK_REMOVE, player).forEach(hide);
      show(qs("." + CSS_CONFIG.ACTIONLINK_ADD_TO_PITCH, player));
      show(qs("." + CSS_CONFIG.ACTIONLINK_ADD_TO_BENCH, player));
    }

    hide(qs("." + CSS_CONFIG.BENCH_PLAYER_REMOVE_LINK, positionDiv));
    hide(qs("." + CSS_CONFIG.BENCH_PLAYER_SUB_LINK, positionDiv));

    var playerIndex =
      Array.prototype.indexOf.call(
        positionDiv.parentElement.children,
        positionDiv
      ) + 1;
    var benchField = qs("#bench" + playerIndex);
    if (benchField) benchField.value = "";
  }

  function addSubstitution(playerInId, playerOutId, minute, condition, position) {
    if (!playerInId || !playerOutId || !minute || minute < 1 || minute > 90) {
      return false;
    }

    var playerIn = qs("#playerinfo" + playerInId);
    var playerOut = qs("#playerinfo" + playerOutId);
    if (!playerIn || !playerOut) return false;

    if (
      !playerIn.classList.contains(CSS_CONFIG.PLAYER_ON_BENCH) ||
      !playerOut.classList.contains(CSS_CONFIG.PLAYER_ON_PITCH)
    ) {
      return false;
    }

    var numberOfExistingSubs = qsa(
      "." + CSS_CONFIG.BENCH_ACTIVE_SUBSTITUTION
    ).length;
    if (numberOfExistingSubs >= 3) return false;

    var benchPosition = qsa("." + CSS_CONFIG.BENCH_POSITION).find(function (el) {
      return String(el.dataset.playerid) === String(playerInId);
    });
    if (!benchPosition) return false;

    var minuteEl = qs(".benchPlayerSubInfoMinute", benchPosition);
    if (minuteEl) minuteEl.textContent = minute;

    var outPlayerInfo = qs(".benchPlayerSubInfoPlayer", benchPosition);
    if (outPlayerInfo) {
      outPlayerInfo.textContent = playerOut.dataset.pname;
      outPlayerInfo.dataset.playerid = playerOutId;
    }

    show(qs(".benchPlayerSubInfo", benchPosition));
    hide(qs(".benchPlayerSubAdd", benchPosition));

    if (condition) {
      show(qs(".benchPlayerSubInfoCondition" + condition, benchPosition));
    }

    if (position) {
      var positionInfoElement = qs(
        ".benchPlayerSubInfoPosition",
        benchPosition
      );
      if (positionInfoElement) {
        show(positionInfoElement);
        var opt = qs('option[value="' + position + '"]', benchPosition);
        var labelEl = qs(".subPositionLabel", positionInfoElement);
        if (opt && labelEl) labelEl.textContent = opt.textContent;
      }
    }

    /* remove selected player from selection for other subs */
    qsa('.playersOutSelection > option[value="' + playerOutId + '"]').forEach(
      function (opt) {
        opt.remove();
      }
    );

    benchPosition.classList.add(CSS_CONFIG.BENCH_ACTIVE_SUBSTITUTION);

    if (numberOfExistingSubs === 2) {
      qsa("." + CSS_CONFIG.BENCH_PLAYER_SUB_LINK).forEach(hide);
    }

    var subNo = numberOfExistingSubs + 1;
    setVal("#sub" + subNo + "_out", playerOutId);
    setVal("#sub" + subNo + "_in", playerInId);
    setVal("#sub" + subNo + "_minute", minute);
    setVal("#sub" + subNo + "_condition", condition);
    setVal("#sub" + subNo + "_position", position);
    return true;
  }

  function setVal(sel, val) {
    var el = qs(sel);
    if (el) el.value = val;
  }

  function removeSubstitution(positionElement) {
    var playerOutId = qs(".benchPlayerSubInfoPlayer", positionElement)
      ? qs(".benchPlayerSubInfoPlayer", positionElement).dataset.playerid
      : null;
    var playerOut = qs("#playerinfo" + playerOutId);

    if (playerOut) {
      qsa(".playersOutSelection").forEach(function (sel) {
        sel.insertAdjacentHTML(
          "beforeend",
          '<option value="' +
            playerOutId +
            '">' +
            escapeHtml(playerOut.dataset.pname) +
            "</option>"
        );
      });
    }

    hide(qs(".benchPlayerSubInfo", positionElement));
    hide(qs(".benchPlayerSubInfoConditionTie", positionElement));
    hide(qs(".benchPlayerSubInfoConditionLeading", positionElement));
    hide(qs(".benchPlayerSubInfoConditionDeficit", positionElement));

    positionElement.classList.remove(CSS_CONFIG.BENCH_ACTIVE_SUBSTITUTION);

    /* display add buttons which have been hidden before */
    qsa(
      "." +
        CSS_CONFIG.BENCH_POSITION +
        ":not(." +
        CSS_CONFIG.BENCH_ACTIVE_SUBSTITUTION +
        "):not(." +
        CSS_CONFIG.PITCH_FREE_POSITION +
        ") .benchPlayerSubAdd"
    ).forEach(show);

    /* remove from hidden input fields */
    var subField = qs('.subsInputOutPlayer[value="' + playerOutId + '"]');
    if (subField) {
      var subNo = subField.dataset.subno;
      if (subNo > 0) {
        setVal("#sub" + subNo + "_out", "");
        setVal("#sub" + subNo + "_in", "");
        setVal("#sub" + subNo + "_minute", "");
        setVal("#sub" + subNo + "_condition", "");
        setVal("#sub" + subNo + "_position", "");
      }
    }
  }

  function positionIsOccupied(testPosition) {
    return !!qs(".position." + testPosition + "." + CSS_CONFIG.PITCH_FREE_POSITION);
  }

  /* ---------- event handlers (delegated) ---------- */
  document.addEventListener("click", function (e) {
    /* remove player link on pitch */
    var removeOnPitch = e.target.closest("." + CSS_CONFIG.PITCH_PLAYER_REMOVE_LINK);
    if (removeOnPitch) {
      e.preventDefault();
      var positionDiv = removeOnPitch.closest("." + CSS_CONFIG.PITCH_POSITION);
      if (positionDiv) removePlayerFromPitch(positionDiv.dataset.playerid);
      return;
    }

    /* remove player link in players selection list */
    var removeLink = e.target.closest("." + CSS_CONFIG.ACTIONLINK_REMOVE);
    if (removeLink) {
      e.preventDefault();
      var player = removeLink.closest("." + CSS_CONFIG.PLAYER_INFO);
      if (!player) return;
      if (player.classList.contains(CSS_CONFIG.PLAYER_ON_PITCH)) {
        removePlayerFromPitch(player.dataset.playerid);
      } else {
        removePlayerFromBench(player.dataset.playerid);
      }
      return;
    }

    /* add player link handler */
    var addPitchItem = e.target.closest(
      "." + CSS_CONFIG.ACTIONLINK_ADD_TO_PITCH_ITEM
    );
    if (addPitchItem) {
      e.preventDefault();
      var p = addPitchItem.closest("." + CSS_CONFIG.PLAYER_INFO);
      if (p) addPlayerToPitch(p.dataset.playerid, addPitchItem.dataset.target);
      return;
    }

    /* add player to bench link handler */
    var addBench = e.target.closest("." + CSS_CONFIG.ACTIONLINK_ADD_TO_BENCH);
    if (addBench) {
      e.preventDefault();
      var pb = addBench.closest("." + CSS_CONFIG.PLAYER_INFO);
      if (pb) addPlayerToBench(pb.dataset.playerid);
      return;
    }

    /* remove player link on bench handler */
    var benchRemove = e.target.closest("." + CSS_CONFIG.BENCH_PLAYER_REMOVE_LINK);
    if (benchRemove) {
      e.preventDefault();
      var bp = benchRemove.closest("." + CSS_CONFIG.BENCH_POSITION);
      if (bp) removePlayerFromBench(bp.dataset.playerid);
      return;
    }

    /* save substitution handler */
    var saveSub = e.target.closest(".saveSubstitutionBtn");
    if (saveSub) {
      var pe = saveSub.closest("." + CSS_CONFIG.BENCH_POSITION);
      if (pe) {
        var playerInId = pe.dataset.playerid;
        var minuteEl = qs('input[id^="sub_minute"]', pe);
        var outSel = qs(".playersOutSelection", pe);
        var condSel = qs('select[id^="sub_condition"]', pe);
        var posSel = qs('select[id^="sub_position"]', pe);
        addSubstitution(
          playerInId,
          outSel ? outSel.value : null,
          minuteEl ? minuteEl.value : null,
          condSel ? condSel.value : null,
          posSel ? posSel.value : null
        );
      }
      return;
    }

    /* remove substitution handler */
    var removeSub = e.target.closest(".removeSubstitutionBtn");
    if (removeSub) {
      e.preventDefault();
      var rpe = removeSub.closest("." + CSS_CONFIG.BENCH_POSITION);
      if (rpe) removeSubstitution(rpe);
      return;
    }

    /* clear all */
    var clearAll = e.target.closest(".clearAllBtn");
    if (clearAll) {
      e.preventDefault();
      qsa("." + CSS_CONFIG.PITCH_POSITION).forEach(function (el) {
        if (el.dataset.playerid) removePlayerFromPitch(el.dataset.playerid);
      });
      qsa("." + CSS_CONFIG.BENCH_POSITION).forEach(function (el) {
        if (el.dataset.playerid) removePlayerFromBench(el.dataset.playerid);
      });
      return;
    }

    /* submit setup form with pre-filled positions */
    var setupSubmit = e.target.closest(".formationSetupSubmit");
    if (setupSubmit) {
      e.preventDefault();
      var preselect = qs("#preselect");
      if (preselect) preselect.value = setupSubmit.dataset.preselect;
      var form = setupSubmit.closest("form");
      if (form) form.submit();
      return;
    }
  });

  /* double click on a pitch position removes the player */
  document.addEventListener("dblclick", function (e) {
    var pos = e.target.closest("." + CSS_CONFIG.PITCH_POSITION);
    if (pos) {
      removePlayerFromPitch(pos.dataset.playerid);
      return;
    }

    var draggable = e.target.closest("." + CSS_CONFIG.PLAYER_DRAGGABLE);
    if (draggable) {
      var position = draggable.dataset.mainposition;

      if (!position || positionIsOccupied(position)) {
        position = draggable.dataset.secondposition;
        if (!position || positionIsOccupied(position)) {
          var positionDiv = qs(
            ".position." +
              CSS_CONFIG.PITCH_FREE_POSITION +
              '[data-position="' +
              draggable.dataset.position +
              '"]'
          );
          if (!positionDiv) return;
          position = positionDiv.dataset.mainposition;
        }
      }
      addPlayerToPitch(draggable.dataset.playerid, position);
    }
  });

  /* ---------- native HTML5 drag & drop ---------- */
  qsa("." + CSS_CONFIG.PLAYER_DRAGGABLE).forEach(function (el) {
    el.draggable = true;
    el.setAttribute("draggable", "true");
  });

  document.addEventListener("dragstart", function (e) {
    var fromPlayer = e.target.closest("." + CSS_CONFIG.PLAYER_DRAGGABLE);
    var fromPosition = e.target.closest("." + CSS_CONFIG.PITCH_POSITION);
    if (fromPlayer && fromPlayer.dataset.playerid) {
      draggedPlayerId = fromPlayer.dataset.playerid;
      try {
        e.dataTransfer.effectAllowed = "move";
        e.dataTransfer.setData("text/plain", draggedPlayerId);
      } catch (err) {}
    } else if (fromPosition && fromPosition.dataset.playerid) {
      draggedPlayerId = fromPosition.dataset.playerid;
      try {
        e.dataTransfer.effectAllowed = "move";
        e.dataTransfer.setData("text/plain", draggedPlayerId);
      } catch (err) {}
    } else {
      draggedPlayerId = null;
    }
  });

  document.addEventListener("dragend", function () {
    draggedPlayerId = null;
    qsa(".playerDropHover").forEach(function (el) {
      el.classList.remove("playerDropHover");
    });
  });

  document.addEventListener("dragover", function (e) {
    var target = e.target.closest("." + CSS_CONFIG.PITCH_POSITION + ", ." + CSS_CONFIG.BENCH_POSITION);
    if (target && draggedPlayerId) {
      e.preventDefault();
      try {
        e.dataTransfer.dropEffect = "move";
      } catch (err) {}
      target.classList.add("playerDropHover");
    }
  });

  document.addEventListener("dragleave", function (e) {
    var target = e.target.closest("." + CSS_CONFIG.PITCH_POSITION + ", ." + CSS_CONFIG.BENCH_POSITION);
    if (target) target.classList.remove("playerDropHover");
  });

  document.addEventListener("drop", function (e) {
    var target = e.target.closest("." + CSS_CONFIG.PITCH_POSITION + ", ." + CSS_CONFIG.BENCH_POSITION);
    if (!target || !draggedPlayerId) return;
    e.preventDefault();
    target.classList.remove("playerDropHover");

    if (target.classList.contains(CSS_CONFIG.PITCH_POSITION)) {
      addPlayerToPitch(draggedPlayerId, target.dataset.mainposition);
    } else {
      addPlayerToBench(draggedPlayerId);
    }
    draggedPlayerId = null;
  });

  /* ---------- pre-select players / subs on load ---------- */
  document.addEventListener("DOMContentLoaded", function () {
    for (var playerIndex = 1; playerIndex <= 11; playerIndex++) {
      var preSelectedPlayer = qs("#player" + playerIndex);
      if (preSelectedPlayer && preSelectedPlayer.value > 0) {
        var posField = qs("#player" + playerIndex + "_pos");
        if (!addPlayerToPitch(preSelectedPlayer.value, posField ? posField.value : null)) {
          preSelectedPlayer.value = "";
        }
      }
    }
    for (var benchIndex = 1; benchIndex <= 5; benchIndex++) {
      var preBench = qs("#bench" + benchIndex);
      if (preBench && preBench.value > 0) {
        if (!addPlayerToBench(preBench.value)) {
          preBench.value = "";
        }
      }
    }
    for (var subNo = 1; subNo <= 3; subNo++) {
      var playerOutId = qs("#sub" + subNo + "_out");
      var playerInId = qs("#sub" + subNo + "_in");
      var minute = qs("#sub" + subNo + "_minute");
      var condition = qs("#sub" + subNo + "_condition");
      var position = qs("#sub" + subNo + "_position");
      if (
        playerOutId &&
        playerInId &&
        minute &&
        playerOutId.value > 0 &&
        playerInId.value > 0 &&
        minute.value > 0
      ) {
        if (
          !addSubstitution(
            playerInId.value,
            playerOutId.value,
            minute.value,
            condition ? condition.value : null,
            position ? position.value : null
          )
        ) {
          setVal("#sub" + subNo + "_out", "");
          setVal("#sub" + subNo + "_in", "");
          setVal("#sub" + subNo + "_minute", "");
          setVal("#sub" + subNo + "_condition", "");
          setVal("#sub" + subNo + "_position", "");
        }
      }
    }

    /* pre-selected free kick taker */
    var fk = qs("#freekickplayer");
    if (fk && fk.dataset.preselect) {
      fk.value = fk.dataset.preselect;
    }
  });
})();
