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

$(function() {

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
		BENCH_ACTIVE_SUBSTITUTION: "benchActiveSubstitution"
	};
	
	// mark position labels as unselectable in order to prevent ugly effects on double click
	$("." + CSS_CONFIG.PITCH_POSITION_LABEL).disableSelection();

	/**
	 * add specified player to specified target position.
	 */
	var addPlayerToPitch = function(playerId, targetPos) {
		var player = $("#playerinfo" + playerId);
		if (player.length == 0) {
			return false;
		}
		
		var targetPosition = $(".position." + targetPos + "." + CSS_CONFIG.PITCH_FREE_POSITION + ":first");
		var playerToSwap = 0;
		
		// is position already occupied?
		if (targetPosition.length == 0) {
			
			targetPosition = $(".position." + targetPos);
			// position does not exist
			if (targetPosition.length == 0) {
				return false;
			}
			
			// it is occupied.
			// prepare player to swap if player-to-add is already on pitch
			if (player.hasClass(CSS_CONFIG.PLAYER_ON_PITCH)) {
				playerToSwap = targetPosition.data("playerid");
			} else {
				return false;
			}
		}
		
		// do not add if player is on bench
		if (player.hasClass(CSS_CONFIG.PLAYER_ON_BENCH)) {
			return false;
		}
		
		// if player has been already on pitch, remove him, because user wants him to move to new position
		if (player.hasClass(CSS_CONFIG.PLAYER_ON_PITCH)) {
			
			var originalPosition = null;
			if (playerToSwap > 0) {
				originalPosition = $("." + CSS_CONFIG.PITCH_POSITION).filter(function() { return ($(this).data("playerid") == playerId); }).data("mainposition");
			}
			
			removePlayerFromPitch(playerId);
			
			// move player-to-swap 
			if (playerToSwap > 0) {
				removePlayerFromPitch(playerToSwap);
				addPlayerToPitch(playerToSwap, originalPosition);
			}
		}
		
		// do not add if blocked or injured
		if (player.data("matchesblocked") > 0) {
			return false;
		}
		
		// check position state
		var playerStrength = player.data("strength");
		if (player.data("mainposition") && targetPosition.hasClass(player.data("mainposition"))
				|| !player.data("mainposition") && targetPosition.data("position") == player.data("position")) {
			targetPosition.addClass(CSS_CONFIG.PITCH_POS_STATE_PRIMARY);
		} else if (targetPosition.hasClass(player.data("secondposition"))
			|| targetPosition.data("position") == player.data("position")) {
			targetPosition.addClass(CSS_CONFIG.PITCH_POS_STATE_SECONDARY);
			playerStrength = player.data("strengthsecondary");
		} else {
			targetPosition.addClass(CSS_CONFIG.PITCH_POS_STATE_WRONG);
			playerStrength = player.data("strengthwrong");
		}
		
		// add ID
		targetPosition.data("playerid", playerId);
		
		// add strength bar
		if (typeof playerStrength !== "undefined") {
			var progress_status = "danger";
			if (playerStrength > 80) {
				progress_status = "success";
			} else if (playerStrength > 50) {
				progress_status = "info";
			} else if (playerStrength > 30) {
				progress_status = "warning";
			}
			targetPosition.append("<div class=\"progress progress-" + progress_status + " " + CSS_CONFIG.PITCH_PLAYER_STRENGTHBAR + "\"><div class=\"bar\" style=\"width: " + playerStrength + "%\">" 
				+ playerStrength + "%</div></div>");
		}
		
		// add picture
		if (player.data("picture")) {
			targetPosition.append("<div class=\"formationPlayerPicture\"><img src=\"" + player.data("picture") + "\"/></div>");
			targetPosition.find(".positionLabel").hide();
		} else {
			targetPosition.addClass("jersey");
		}
		
		// add name div
		targetPosition.append("<div class=\"" + CSS_CONFIG.PITCH_PLAYER_NAME + "\">" + player.data("pname") + "</div>");
		
		// add remove icon
		targetPosition.append("<a class=\"" + CSS_CONFIG.PITCH_PLAYER_REMOVE_LINK + "\" href=\"#\"><i class=\"icon-remove darkIcon\"></i></a>");
		
		// mark as on pitch
		player.addClass(CSS_CONFIG.PLAYER_ON_PITCH);
		
		// hide and show action links
		player.find("." + CSS_CONFIG.ACTIONLINK_REMOVE).css("display", "inline-block");
		player.find("." + CSS_CONFIG.ACTIONLINK_ADD_TO_PITCH).hide();
		player.find("." + CSS_CONFIG.ACTIONLINK_ADD_TO_BENCH).hide();
		
		targetPosition.removeClass(CSS_CONFIG.PITCH_FREE_POSITION);
		
		// add to hidden input field
		var playerField = $(".playerField[value=" + playerId + "]");
		if (!playerField.length) {
			playerField = $(".playerField[value='']:first");
			playerField.val(playerId);
		}
		playerField.next().val(targetPos.substring(0, 2));
		
		// add to selection for substitutions
		$(".playersOutSelection").append("<option value=\"" + playerId + "\">" + player.data("pname") + "</option>");
		
		// add to free kick taker selection
		$("#freekickplayer").append("<option value=\"" + playerId + "\">" + player.data("pname") + "</option>");
		
		// make draggable in order to move to new position
		targetPosition.draggable({ 
			revert: "invalid", 
			helper: "clone" 
		});
		
		return true;
	};
	
	/**
	 * Remove specified player from pitch
	 */
	var removePlayerFromPitch = function(playerId) {
		var positionDiv = $("." + CSS_CONFIG.PITCH_POSITION).filter(function() { return ($(this).data("playerid") == playerId); });
		
		positionDiv.removeData("playerid");
		positionDiv.find("." + CSS_CONFIG.PITCH_PLAYER_REMOVE_LINK).remove();
		positionDiv.find("." + CSS_CONFIG.PITCH_PLAYER_NAME ).remove();
		positionDiv.find("." + CSS_CONFIG.PITCH_PLAYER_STRENGTHBAR ).remove();
		
		positionDiv.removeClass(CSS_CONFIG.PITCH_POS_STATE_PRIMARY)
			.removeClass(CSS_CONFIG.PITCH_POS_STATE_SECONDARY)
			.removeClass(CSS_CONFIG.PITCH_POS_STATE_WRONG);
		
		positionDiv.addClass(CSS_CONFIG.PITCH_FREE_POSITION);
		
		var player = $("#playerinfo" + playerId);
		player.removeClass(CSS_CONFIG.PLAYER_ON_PITCH);
		player.find("." + CSS_CONFIG.ACTIONLINK_REMOVE).hide();
		player.find("." + CSS_CONFIG.ACTIONLINK_ADD_TO_PITCH).show();
		player.find("." + CSS_CONFIG.ACTIONLINK_ADD_TO_BENCH).show();
		
		// remove picture
		if (player.data("picture")) {
			positionDiv.find(".formationPlayerPicture").remove();
			positionDiv.find(".positionLabel").show();
		} else {
			positionDiv.removeClass("jersey");
		}
		
		// remove selected player from selection for substitutions
		$(".playersOutSelection >option[value=\"" + playerId + "\"]").remove();
		
		// remove from free kick taker selection
		$("#freekickplayer >option[value=\"" + playerId + "\"]").remove();
		
		// remove from hidden input field
		var playerField = $(".playerField[value=" + playerId + "]");
		playerField.val("");
		playerField.next().val("");
	};
	
	/**
	 * Adds specified player to bench, if bench is not full.
	 * @returns false if not added (because player is invalid or already set), true if added.
	 */
	var addPlayerToBench = function(playerId) {
		var player = $("#playerinfo" + playerId);
		var targetPosition = $("." + CSS_CONFIG.BENCH_POSITION + "." + CSS_CONFIG.PITCH_FREE_POSITION + ":first");
		if (player.length == 0 || targetPosition.length == 0) {
			return false;
		}
		
		// do not add if already on pitch or bench
		if (player.hasClass(CSS_CONFIG.PLAYER_ON_PITCH) || player.hasClass(CSS_CONFIG.PLAYER_ON_BENCH)) {
			return false;
		}
		
		// do not add if blocked or injured
		if (player.data("matchesblocked") > 0) {
			return false;
		}
		
		// mark as on pitch
		player.addClass(CSS_CONFIG.PLAYER_ON_BENCH);
		
		targetPosition.data("playerid", playerId);
		
		// create player info at bench
		var playerInfoCell = targetPosition.find(" >.benchPlayerInfo");
		playerInfoCell.find(" >.benchPlaceholder").hide();
		
		var playerLabel = player.data("pname");
		if (player.data("mainposition")) {
			playerLabel = playerLabel + " (" + player.find(".mainposition").text();
			if (player.data("secondposition")) {
				playerLabel = playerLabel + " / " + player.find(".secondposition").text();
			}
			playerLabel = playerLabel + ")";
		}
		
		playerInfoCell.append("<span class=\"benchPlayer\">" + playerLabel + "</span>");
		
		// hide and show action links
		player.find("." + CSS_CONFIG.ACTIONLINK_REMOVE).css("display", "inline-block");
		player.find("." + CSS_CONFIG.ACTIONLINK_ADD_TO_PITCH).hide();
		player.find("." + CSS_CONFIG.ACTIONLINK_ADD_TO_BENCH).hide();
		
		targetPosition.find("." + CSS_CONFIG.BENCH_PLAYER_REMOVE_LINK).show();
		
		if ($("." + CSS_CONFIG.BENCH_ACTIVE_SUBSTITUTION).length < 3) {
			targetPosition.find("." + CSS_CONFIG.BENCH_PLAYER_SUB_LINK).show();
		}
		
		targetPosition.removeClass(CSS_CONFIG.PITCH_FREE_POSITION);
		
		// add to hidden input field
		var playerIndex = targetPosition.index() + 1;
		$("#bench" + playerIndex).val(playerId);
		
		return true;
	};
	
	/**
	 * Remove specified player from bench
	 */
	var removePlayerFromBench = function(playerId) {
		var positionDiv = $("." + CSS_CONFIG.BENCH_POSITION).filter(function() { return ($(this).data("playerid") == playerId); });
		
		removeSubstitution(positionDiv);
		
		positionDiv.removeData("playerid");
		
		positionDiv.addClass(CSS_CONFIG.PITCH_FREE_POSITION);
		
		positionDiv.find(".benchPlayer").remove();
		positionDiv.find(".benchPlaceholder").show();
		
		var player = $("#playerinfo" + playerId);
		player.removeClass(CSS_CONFIG.PLAYER_ON_BENCH);
		player.find("." + CSS_CONFIG.ACTIONLINK_REMOVE).hide();
		player.find("." + CSS_CONFIG.ACTIONLINK_ADD_TO_PITCH).show();
		player.find("." + CSS_CONFIG.ACTIONLINK_ADD_TO_BENCH).show();
		
		positionDiv.find("." + CSS_CONFIG.BENCH_PLAYER_REMOVE_LINK).hide();
		positionDiv.find("." + CSS_CONFIG.BENCH_PLAYER_SUB_LINK).hide();
		
		// remove from hidden input field
		var playerIndex = positionDiv.index() + 1;
		$("#bench" + playerIndex).val("");
	};
	
	/**
	 * Add substitution. Saves it to hidden input fields and enables details display.
	 */
	var addSubstitution = function(playerInId, playerOutId, minute, condition, position) {
		if (!playerInId || !playerOutId || !minute || minute < 1 || minute > 90) {
			return false;
		}
		
		var playerIn = $("#playerinfo" + playerInId);
		var playerOut = $("#playerinfo" + playerOutId);
		
		if (playerIn.length == 0 || playerOut.length == 0) {
			return false;
		}
		
		// check if players are on pitch respectively on bench
		if (!playerIn.hasClass(CSS_CONFIG.PLAYER_ON_BENCH) || !playerOut.hasClass(CSS_CONFIG.PLAYER_ON_PITCH)) {
			return false;
		}
		
		// check if already 3 subs configured
		var numberOfExistingSubs = $("." + CSS_CONFIG.BENCH_ACTIVE_SUBSTITUTION).length;
		if (numberOfExistingSubs >= 3) {
			return false;
		}
		
		// get bench position
		var benchPosition = $("." + CSS_CONFIG.BENCH_POSITION).filter(function() { return ($(this).data("playerid") == playerInId); });
		
		// insert information at bench
		benchPosition.find(".benchPlayerSubInfoMinute").text(minute);
		
		var outPlayerInfo = benchPosition.find(".benchPlayerSubInfoPlayer");
		outPlayerInfo.text(playerOut.data("pname"));
		outPlayerInfo.data("playerid", playerOutId);
		
		benchPosition.find(".benchPlayerSubInfo").show();
		
		benchPosition.find(".benchPlayerSubAdd").hide();
		
		if (condition) {
			benchPosition.find(".benchPlayerSubInfoCondition" + condition).show();
		}
		
		if (position) {
			var positionInfoElement = benchPosition.find(".benchPlayerSubInfoPosition");
			positionInfoElement.show();
			positionInfoElement.find(".subPositionLabel").text(benchPosition.find("option[value=" +  position + "]").text());
		}
		
		// remove selected player from selection for other subs
		$(".playersOutSelection >option[value=\"" + playerOutId + "\"]").remove();
		
		benchPosition.addClass(CSS_CONFIG.BENCH_ACTIVE_SUBSTITUTION);
		
		// hide substitutin buttons in case this is the third sub
		if (numberOfExistingSubs == 2) {
			$("." + CSS_CONFIG.BENCH_PLAYER_SUB_LINK).hide();
		}
		
		// add to hidden input fields
		var subNo = numberOfExistingSubs + 1;
		$("#sub" + subNo + "_out").val(playerOutId);
		$("#sub" + subNo + "_in").val(playerInId);
		$("#sub" + subNo + "_minute").val(minute);
		$("#sub" + subNo + "_condition").val(condition);
		$("#sub" + subNo + "_position").val(position);
		return true;
	};
	
	// remove player link on pitch handler
	$(document).on("click", "." + CSS_CONFIG.PITCH_PLAYER_REMOVE_LINK, function(event){
		event.preventDefault();
		
		var positionDiv = $(this).parent();
		removePlayerFromPitch(positionDiv.data("playerid"));
	});
	
	// remove player link handler (link in players selection list)
	$("." + CSS_CONFIG.ACTIONLINK_REMOVE).click(function(event) {
		event.preventDefault();
		
		var player = $(this).closest("." + CSS_CONFIG.PLAYER_INFO);
		if (player.hasClass(CSS_CONFIG.PLAYER_ON_PITCH)) {
			removePlayerFromPitch(player.data("playerid"));
		} else {
			removePlayerFromBench(player.data("playerid"));
		}
		
	});
	
	// enable player removal by double click
	$("." + CSS_CONFIG.PITCH_POSITION).dblclick(function(event) {

		removePlayerFromPitch($(this).data("playerid"));
	});
	
	// add player link handler
	$("." + CSS_CONFIG.ACTIONLINK_ADD_TO_PITCH_ITEM).click(function(event) {
		event.preventDefault();
		
		var player = $(this).closest("." + CSS_CONFIG.PLAYER_INFO);
		addPlayerToPitch(player.data("playerid"), $(this).data("target"));
	});
	
	// enable dragging
	$("." + CSS_CONFIG.PLAYER_DRAGGABLE).draggable({ 
		revert: "invalid", 
		helper: "clone" 
	});
	
	// enable dropping on pitch
	$("." + CSS_CONFIG.PITCH_POSITION).droppable({ 
		hoverClass: "playerDropHover",
		drop: function(event, ui) {
			addPlayerToPitch(ui.draggable.data("playerid"), $(this).data("mainposition"));
		}
	});
	
	// enable dropping on bench
	$("." + CSS_CONFIG.BENCH_POSITION).droppable({ 
		hoverClass: "playerDropHover",
		drop: function(event, ui) {
			addPlayerToBench(ui.draggable.data("playerid"));
		}
	});
	
	var positionIsOccupied = function(testPosition) {
		if ($(".position." + testPosition + "." + CSS_CONFIG.PITCH_FREE_POSITION + ":first").length == 0) {
			return true;
		}
		
		return false;
	};
	
	// enable double clicking on a player, which tries to move him to his primary position. If occupied, use secondary position
	$("." + CSS_CONFIG.PLAYER_DRAGGABLE).dblclick(function(event) { 
		var position = $(this).data("mainposition");
		
		if (!position || positionIsOccupied(position)) {
			
			// take secondary position
			position = $(this).data("secondposition");
			
			// else take any free position within his position
			if (!position || positionIsOccupied(position)) {
				positionDiv = $(".position." + CSS_CONFIG.PITCH_FREE_POSITION + "[data-position=" + $(this).data("position") + "]:first");
				
				// also everything occupied, then cancel
				if (positionDiv.length == 0) {
					return;
				}
				
				position = positionDiv.data("mainposition");
			}
		}
		
		addPlayerToPitch($(this).data("playerid"), position);
	});
	
	// add player to bench link handler
	$("." + CSS_CONFIG.ACTIONLINK_ADD_TO_BENCH).click(function(event) {
		event.preventDefault();
		
		var player = $(this).closest("." + CSS_CONFIG.PLAYER_INFO);
		addPlayerToBench(player.data("playerid"));
	});
	
	// remove player link on pitch handler
	$(document).on("click", "." + CSS_CONFIG.BENCH_PLAYER_REMOVE_LINK, function(event){
		event.preventDefault();
		
		var positionDiv = $(this).closest("." + CSS_CONFIG.BENCH_POSITION);
		removePlayerFromBench(positionDiv.data("playerid"));
	});
	
	// save substitution handler
	$(".saveSubstitutionBtn").click(function(event) {
		
		var positionElement = $(this).closest("." + CSS_CONFIG.BENCH_POSITION);
		
		var playerInId = positionElement.data("playerid");
		var minute = positionElement.find("input[id^=\"sub_minute\"]").val();
		var playerOutId = positionElement.find(".playersOutSelection").val();
		var condition = positionElement.find("select[id^=\"sub_condition\"]").val();
		var position = positionElement.find("select[id^=\"sub_position\"]").val();
		
		addSubstitution(playerInId, playerOutId, minute, condition, position);
	});
	
	// remove substitution handler
	$(".removeSubstitutionBtn").click(function(event) {
		event.preventDefault();
		
		var positionElement = $(this).closest("." + CSS_CONFIG.BENCH_POSITION);
		removeSubstitution(positionElement);

	});
	
	/**
	 * Cancels the specified substitution.
	 */
	var removeSubstitution = function(positionElement) {
		// add player back to substitution selection
		var playerOutId = positionElement.find(".benchPlayerSubInfoPlayer").data("playerid");
		var playerOut = $("#playerinfo" + playerOutId);
		$(".playersOutSelection").append("<option value=\"" + playerOutId + "\">" + playerOut.data("pname") + "</option>");
		
		positionElement.find(".benchPlayerSubInfo").hide();
		
		positionElement.find(".benchPlayerSubInfoConditionTie").hide();
		positionElement.find(".benchPlayerSubInfoConditionLeading").hide();
		positionElement.find(".benchPlayerSubInfoConditionDeficit").hide();
		
		positionElement.removeClass(CSS_CONFIG.BENCH_ACTIVE_SUBSTITUTION);
		
		// display add buttons which have been hided before
		$("." + CSS_CONFIG.BENCH_POSITION + ":not(." + CSS_CONFIG.BENCH_ACTIVE_SUBSTITUTION + ",." + CSS_CONFIG.PITCH_FREE_POSITION + ") .benchPlayerSubAdd").show();
	
		// remove from hidden input fields
		var subNo = $(".subsInputOutPlayer[value=\"" + playerOutId + "\"]").data("subno");
		if (subNo > 0) {
			$("#sub" + subNo + "_out").val("");
			$("#sub" + subNo + "_in").val("");
			$("#sub" + subNo + "_minute").val("");
			$("#sub" + subNo + "_condition").val("");
			$("#sub" + subNo + "_position").val("");
		}
		
	};
	
	// clear all
	$(".clearAllBtn").click(function(event) {
		event.preventDefault();
		
		$("." + CSS_CONFIG.PITCH_POSITION).each(function(index, element) {
			removePlayerFromPitch($(this).data("playerid"));
		});
		
		$("." + CSS_CONFIG.BENCH_POSITION).each(function(index, element) {
			removePlayerFromBench($(this).data("playerid"));
		});
	});
	
	// submit setup form with pre-filled positions
	$(".formationSetupSubmit").click(function(event) {
		event.preventDefault();
		
		$("#preselect").val($(this).data("preselect"));
		
		$(this).closest("form").submit();
	});
	
	// add pre-selected players on document load
	for (var playerIndex = 1; playerIndex <= 11; playerIndex++) {
		var preSelectedPlayer = $("#player" + playerIndex).val();
		if (preSelectedPlayer > 0) {
			var playerAdded = addPlayerToPitch(preSelectedPlayer, $("#player" + playerIndex + "_pos").val());
			if (!playerAdded) {
				$("#player" + playerIndex).val("");
			}
		}
	}
	for (var benchIndex = 1; benchIndex <= 5; benchIndex++) {
		var preSelectedPlayer = $("#bench" + benchIndex).val();
		if (preSelectedPlayer > 0) {
			var benchPlayerAdded = addPlayerToBench(preSelectedPlayer);
			if (!benchPlayerAdded) {
				$("#bench" + benchIndex).val("");
			}
		}
	}
	
	// add pre-saved substitutions
	for (var subNo = 1; subNo <= 3; subNo++) {
		var playerOutId = $("#sub" + subNo + "_out").val();
		var playerInId = $("#sub" + subNo + "_in").val();
		var minute = $("#sub" + subNo + "_minute").val();
		var condition = $("#sub" + subNo + "_condition").val();
		var position = $("#sub" + subNo + "_position").val();
		if (playerOutId > 0 && playerInId > 0 && minute > 0) {
			var subAdded = addSubstitution(playerInId, playerOutId, minute, condition, position);
			if (!subAdded) {
				$("#sub" + subNo + "_out").val("");
				$("#sub" + subNo + "_in").val("");
				$("#sub" + subNo + "_minute").val("");
				$("#sub" + subNo + "_condition").val("");
				$("#sub" + subNo + "_position").val("");
			}
		}
	}
	
	// pre-selected free kick taker
	var preSelectedFreekickPlayer = $("#freekickplayer").data("preselect");
	if (preSelectedFreekickPlayer) {
		$("#freekickplayer").val(preSelectedFreekickPlayer);
	}
	
});
