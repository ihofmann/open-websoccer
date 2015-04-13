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
	
	var WSCONFIG = {
			AJAX_URL: "ajax.php"
	};
	
	/**
	 * percent value setting sliders
	 */
	$(".slider").each(function(i, component) {
		$(component).slider().on('slideStop', function(ev){
			$("input.slider").attr('value', ev.value);
		});
	});
	
	/**
	 * Initializations of general components.
	 */
	var initComponents = function() {
		$(".wstooltip").tooltip();
		$(".wspopover").popover();
		initAutoComplete();
	};
	
	/**
	 * autocomplete AJAX support
	 */
	var initAutoComplete = function() {
		$(".autocomplete").typeahead({
			minLength: 2,
			
			source: function(query, process) {
		        return $.ajax({
		            url: WSCONFIG.AJAX_URL,
		            type: 'get',
		            data: {contentonly: 1, block: $(this)[0].$element.data("ajaxblock"), query: query},
		            dataType: 'json',
		            success: function(json) {
		                return typeof json.options == 'undefined' ? false : process(json.options);
		            }
		        });
		    }
		});
	};
	
	/**
	 * AJAXified forms
	 */
	$(document).on("click", ".ajaxSubmit", function(e) {
		e.preventDefault();
		
		var form = $(this).closest("form");
		form.submit(function() {
			  return false;
		});
		
		ajaxHandler(form.serialize(), $(this).data("ajaxtarget"), $(this).data("ajaxblock"), $(this).data("messagetarget"), form, $(this).data("ignoreemptymessages"));
	});
	
	/**
	 * AJAXified links
	 */
	$(document).on("click", ".ajaxLink", function(e) {
		e.preventDefault();
		
		var targetId = $(this).data("ajaxtarget");
		if (!$(this).data("ajaxloaded") || $(this).data("ajaxdisabledcache")) {
			ajaxHandler($(this).data("ajaxquerystr"), targetId, $(this).data("ajaxblock"), $(this).data("messagetarget"), 
					$("#" + targetId).closest("div"), $(this).data("ignoreemptymessages"));
			
			// cache only if area is not updated by any other link
			if ($("a[data-ajaxtarget='" + targetId + "']").length < 2) {
				$(this).data("ajaxloaded", "1");
			}
			
		}
	});
	
	var ajaxHandler = function(queryString, targetId, blockId, messagesTargetId, blockedElement, ignoreemptymessages) {
		if (!blockId) {
			blockId = "";
		}
		var requestUrl = WSCONFIG.AJAX_URL + "?block=" + blockId + "&" + queryString;
		var ajaxLoader = $("#ajaxLoaderPage");
		$.ajax({
				url: requestUrl,
				dataType: "json",
				beforeSend: function() {
					ajaxLoader.show();
					blockedElement.block({ message: null });
				}
			})
			.done(function(data) {
				$("#" + targetId).html(data.content);
				
				if (!ignoreemptymessages || data.messages.trim().length) {
					var msgTargetId = (messagesTargetId) ? messagesTargetId : "messages";
					$("#" + msgTargetId).html(data.messages);
				}
				
			})
			.always(function() { 
				blockedElement.unblock();
				ajaxLoader.hide();
			});
	};
	
	// enable browser history after AJAX link
	$(document).ready(function() {
		var hash = location.hash.replace('#', '');
		if (hash.length > 0) {
			$("a.ajaxLink[href$='#" + hash + "']").click();
		}
	});
	
	// enable browser history for tab panes
	$('a[data-toggle="tab"]').on('shown', function (e) {
		  window.location.hash = e.target.hash;
	});
	
	// enable client side "active" marker of nav items
	$(document).on('click', '.clientsideNavItem', function (e) {
		$(this).parent().find("> .active").removeClass("active");
        $(this).addClass("active");
	});
	
	/**
	 * Init Countdown
	 */
	$(".countdown").each(function(i, component) {
		$(component).countdown($(component).data("date"), function(event) {
			var $this = $(this);
			switch (event.type) {
				case "seconds":
				case "minutes":
				case "hours":
				case "days":
				case "daysLeft":
					$this.find('#' + event.type).html(event.value);
					break;
				case "finished":
					$this.hide();
					break;
			}
		});
	});
	
	/**
	 * init notifications popup
	 */
	$("#notificationsLink").popover({ 
	    html: true, 
	    placement: "bottom",
	    content: function() {
	    	var contentHtml = $("#notificationspopupwrapper").html();
	    	$("#notificationspopupwrapper").remove();
	    	return contentHtml;
	    }
	});
	
	var triggerAjaxLinksOnLoad = function() {
		$(".triggerClickOnLoad:not(.clicked)").trigger("click");
		$(".triggerClickOnLoad").addClass("clicked");
	};
	
	var triggerAjaxLoadOfBlocks = function() {
		$(".ajaxLoadedBlock").each(function() {
			var queryStr = $(this).data("ajaxquerystr");
			var elementId = $(this).attr("id");
			var blockId = $(this).data("ajaxblock");
			var messagesTarget = $(this).data("messagetarget");
			var elementToBlock = $(this);
			var ignoreEmptyMessages = $(this).data("ignoreemptymessages");
			var refreshPeriod = $(this).data("refreshperiod");
			
			ajaxHandler(queryStr, elementId, blockId, messagesTarget, 
					elementToBlock, ignoreEmptyMessages);
			
			// refresh after X seconds
			if (refreshPeriod) {
				setInterval(function () {
					ajaxHandler(queryStr, elementId, blockId, messagesTarget, 
							elementToBlock, ignoreEmptyMessages);
			    }, refreshPeriod * 1000);
			}
			
		});
		
	};
	
	// init components which can be re-rendered on AJX calls
	$(document).ready(function() {
		initComponents();
		triggerAjaxLinksOnLoad();
		triggerAjaxLoadOfBlocks();
		
	});
	$(document).ajaxComplete(function() {
		initComponents();
		blockMatchRefreshButton();
		triggerAjaxLinksOnLoad();
	});
	
	/**
	 * Block AJAX refresh button at match reports
	 */
	var refreshCountdownStarted = false;
	var blockMatchRefreshButton = function() {
		$("#matchReportRefresh").each(function() {
			var timeToBlock = $(this).data("blockseconds");
			
			if (!refreshCountdownStarted) {
				$(this).each(function() {
			        $(this).attr('disabled', 'disabled');
			        var disabledElem = $(this);
			        var countdownElement = disabledElem.children(".timerCount");
			        var interval = setInterval(function() {
			        	refreshCountdownStarted = true;
			        	countdownElement.text( '(' + --timeToBlock + ')' );
			            if (timeToBlock === 0) {
			                disabledElem.removeAttr('disabled');
			                countdownElement.text('');
			                refreshCountdownStarted = false;
			                clearInterval(interval);
			                
			                // automatic refresh
			                $("#matchReportRefresh").trigger("click");
			            }
			        }, 1000);
			    });
			}
			
		});
	};
	
});
