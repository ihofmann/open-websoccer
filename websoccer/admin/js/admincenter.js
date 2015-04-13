function selectAll() {
  var c = document.frmMain.selAll.checked;

  for (var i = 0; i < document.frmMain.elements.length; i++) {
    if (document.frmMain.elements[i].type == "checkbox" && document.frmMain.elements[i].name != document.frmMain.selAll.name) {
      document.frmMain.elements[i].checked = c;
    }
  }

}

$(function() {
	
	// Primary key picker
	$(".pkpicker").select2({
        minimumInputLength: 2,
        placeholder: $(this).data("placeholder"),
        allowClear: true,
        ajax: { 
            url: "itemsprovider.php",
            dataType: 'json',
            data: function (term, page) {
                return {
                    search: term, // search term
                    dbtable: $(this).data("dbtable"),
                    labelcolumns: $(this).data("labelcolumns")
                };
            },
            results: function (data, page) {
                return {results: data};
            }
        },
        initSelection: function(element, callback) {
        	var id = $(element).val();
            if (id !== "" && id !== "0") {
                $.ajax("itemsprovider.php", {
                    data: {
                    	itemid: id, // search term
                        dbtable: $(element).data("dbtable"),
                        labelcolumns: $(element).data("labelcolumns")
                    },
                    dataType: "json"
                }).done(function(data) { callback(data[0]); });
            }
        }
	});

	// time picker
	$(".timepicker").timepicker({
	    minuteStep: 1,
	    showMeridian: false
	});
	
	// HTML editor
	$(".htmleditor").markItUp(mySettings);
	
	// start (cron) job
	$(document).on("click", ".startStopJobLink", function(e) {
		e.preventDefault();
		
		var requestUrl = $(this).attr("href");
		$.ajax({
			url: requestUrl,
			timeout: 3000,
			beforeSend: function() {
				$("#ajaxSpinner").show();
			}
		})
		.always(function() { 
			$("#ajaxSpinner").hide();
			location.reload(true);
		});
	});
	
	// enable table row multiple selection
	$(".tableRowSelectionCell").click(function() {
		$(this).parent().find(" input:checkbox").trigger("click");
	});
	
	// select teams for cup match creation
	$(".teamForCupCheckbox").change(function() {
		teamForCupChangeHandler();
	});
	var teamForCupChangeHandler = function() {
		var noOfTeams = $(".teamForCupCheckbox:checked").length;
		var noOfRounds = Math.log(noOfTeams) / Math.LN2;
		
		// change status
		$("#numberOfTeamsSelected").text(noOfTeams);
		
		// 0 rounds or not a natural number
		if (noOfRounds == 0 || !/^(0|([1-9]\d*))$/.test(noOfRounds)) {
			$("#noCupPossibleAlert").show(500);
			$("#possibleCupRoundsAlert").hide(500);
			return;
		}
		
		// rounds are possible
		$("#possibleCupRoundsAlert").show(500);
		$("#noCupPossibleAlert").hide(500);
		
		$("#roundsNo").text(noOfRounds);
	};
	
});
