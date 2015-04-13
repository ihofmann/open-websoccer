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
	
	var stadiumGraph = {
		CONFIG: {
			GRAND_WIDTH: 380,
			GRAND_HEIGHT: 90,
			SIDE_WIDTH: 110,
			SIDE_HEIGHT: 300,
			VIP_HEIGHT: 40,
			MAX_SIZE_STYLE: "#F5F5F5",
			LABEL_BACKGROUND: "red",
			FONT_COLOR: "white",
			IMG_SEAT: "img/seat.png",
			IMG_VIP: "img/seat_vip.png",
			IMG_STANDING: "img/standing.png"
		},
		
		stadiumCanvas: null,
		
		canvasContext: null,
		
		/**
		 * draws the whole stadium
		 */
		drawStadium: function(elementId) {
			this.stadiumCanvas = document.getElementById(elementId);
			this.canvasContext = this.stadiumCanvas.getContext("2d");
			this.canvasContext.font = "normal 12px Arial";
			
			this.canvasContext.fillStyle = this.CONFIG.MAX_SIZE_STYLE;
			
			// show maximum size
			this._drawGrandTop(100);
			this._drawGrandBottom(100);
			this._drawSideLeft(100);
			this._drawSideRight(100);
			
			var graphInstance = this;
			var ratioGrand = $(this.stadiumCanvas).data("ratiogrand");
			var ratioSide = $(this.stadiumCanvas).data("ratioside");
			
			// show actual size
			var imageObj = new Image();
			imageObj.onload = function() {
				var pattern = graphInstance.canvasContext.createPattern(
						imageObj, "repeat");
				graphInstance.canvasContext.fillStyle = pattern;
				
				graphInstance._drawGrandTop(ratioGrand);
				graphInstance._drawGrandBottom(ratioGrand, true);
				
				graphInstance._drawSideRight(ratioSide, true);
				
				graphInstance._drawSideLeft(ratioSide, true);
				
				// show VIP
				var ratioVip = $(graphInstance.stadiumCanvas).data("ratiovip");
				graphInstance._drawVip(ratioVip);
			};
			imageObj.src = this.CONFIG.IMG_SEAT;
			
		},
	
		_drawGrandTop: function(perCent) {
			var targetY = this.CONFIG.GRAND_HEIGHT - perCent / 100 * this.CONFIG.GRAND_HEIGHT;
			
			this.canvasContext.fillRect(this.CONFIG.SIDE_WIDTH, targetY, this.CONFIG.GRAND_WIDTH, this.CONFIG.GRAND_HEIGHT - targetY);
		},
		
		_drawGrandBottom: function(perCent, drawStanding) {
			var targetY = this.CONFIG.GRAND_HEIGHT - perCent / 100 * this.CONFIG.GRAND_HEIGHT;
			
			var startX = this.CONFIG.SIDE_WIDTH;
			var startY = this.stadiumCanvas.height - this.CONFIG.GRAND_HEIGHT;
			
			this.canvasContext.fillRect(startX, 
					startY, 
					this.CONFIG.GRAND_WIDTH, this.CONFIG.GRAND_HEIGHT - targetY);
			
			// draw standings
			if (drawStanding) {
				var graphInstance = this;
				var imageObj = new Image();
				imageObj.onload = function() {
					var pattern = graphInstance.canvasContext.createPattern(
							imageObj, "repeat");
					graphInstance.canvasContext.fillStyle = pattern;
					
					var standingWidth = $(graphInstance.stadiumCanvas).data("standingratiogrand") / 100 * graphInstance.CONFIG.GRAND_WIDTH;
					startX = startX + graphInstance.CONFIG.GRAND_WIDTH / 2 - standingWidth / 2;
					graphInstance.canvasContext.fillRect(startX, 
							startY, 
							standingWidth, graphInstance.CONFIG.GRAND_HEIGHT - targetY);
					
					graphInstance._drawGrandLabel();
					
				};
				imageObj.src = this.CONFIG.IMG_STANDING;
			}
			
		},
		
		_drawVip: function(perCent) {
			this.canvasContext.fillStyle = this.CONFIG.ACTUAL_SIZE_VIP_STYLE;
			
			var maxWidth = 0.9 * this.CONFIG.GRAND_WIDTH;
			var actualWidth = maxWidth * perCent / 100;
			var startX = this.CONFIG.SIDE_WIDTH + this.CONFIG.GRAND_WIDTH / 2 - actualWidth / 2;
			var startY = this.CONFIG.GRAND_HEIGHT - this.CONFIG.VIP_HEIGHT;
			
			var graphInstance = this;
			var imageObj = new Image();
			imageObj.onload = function() {
				var pattern = graphInstance.canvasContext.createPattern(
						imageObj, "repeat");
				graphInstance.canvasContext.fillStyle = pattern;
				graphInstance.canvasContext.fillRect(startX, startY, actualWidth, graphInstance.CONFIG.VIP_HEIGHT);
				
				graphInstance._drawVipLabel();
			};
			imageObj.src = this.CONFIG.IMG_VIP;
			
		},
		
		_drawSideLeft: function(perCent, drawStanding) {
			
			this.canvasContext.beginPath();
			
			// left top
			var targetY = this.CONFIG.SIDE_WIDTH - perCent / 100 * this.CONFIG.SIDE_WIDTH;
			this.canvasContext.moveTo(this.CONFIG.SIDE_WIDTH, targetY);
			
			var targetX = this.CONFIG.SIDE_WIDTH - perCent / 100 * this.CONFIG.SIDE_WIDTH;
			this.canvasContext.quadraticCurveTo(targetX + 10, targetY, targetX, this.CONFIG.GRAND_HEIGHT);
			
			// go down
			this.canvasContext.lineTo(targetX, this.stadiumCanvas.height - this.CONFIG.GRAND_HEIGHT);
			
			// left bottom
			this.canvasContext.quadraticCurveTo(targetX + 10, this.stadiumCanvas.height - targetY, this.CONFIG.SIDE_WIDTH, this.stadiumCanvas.height - targetY);
			
			//draw
			this.canvasContext.closePath();
			this.canvasContext.lineWidth = 1;
			this.canvasContext.fill();
			
			// draw standings
			if (drawStanding) {
				var graphInstance = this;
				var imageObj = new Image();
				imageObj.onload = function() {
					var pattern = graphInstance.canvasContext.createPattern(
							imageObj, "repeat");
					graphInstance.canvasContext.fillStyle = pattern;
					
					var standingWidth = graphInstance.CONFIG.SIDE_WIDTH - targetX;
					var standingHeight = $(graphInstance.stadiumCanvas).data("standingratioside") / 100 * (graphInstance.stadiumCanvas.height - graphInstance.CONFIG.GRAND_HEIGHT - targetY);
					var startY = graphInstance.stadiumCanvas.height / 2 - standingHeight / 2;
						
					graphInstance.canvasContext.fillRect(targetX, 
							startY, 
							standingWidth, standingHeight);
					
					graphInstance._drawSideLabel();
					
				};
				imageObj.src = this.CONFIG.IMG_STANDING;
			}
		},
		
		_drawSideRight: function(perCent, drawStanding) {
			
			this.canvasContext.beginPath();
			
			// right top
			var targetY = this.CONFIG.SIDE_WIDTH - perCent / 100 * this.CONFIG.SIDE_WIDTH;
			this.canvasContext.moveTo(this.CONFIG.SIDE_WIDTH + this.CONFIG.GRAND_WIDTH, targetY);
			
			var targetX = this.CONFIG.SIDE_WIDTH - perCent / 100 * this.CONFIG.SIDE_WIDTH;
			this.canvasContext.quadraticCurveTo(this.stadiumCanvas.width - targetX - 10, targetX, this.stadiumCanvas.width - targetX, this.CONFIG.GRAND_HEIGHT);
			
			// go down
			this.canvasContext.lineTo(this.stadiumCanvas.width - targetX, this.stadiumCanvas.height - this.CONFIG.GRAND_HEIGHT);
			
			// left bottom
			this.canvasContext.quadraticCurveTo(this.stadiumCanvas.width - targetX - 10, this.stadiumCanvas.height - targetY, this.CONFIG.SIDE_WIDTH + this.CONFIG.GRAND_WIDTH, this.stadiumCanvas.height - targetY);
			
			//draw
			this.canvasContext.closePath();
			this.canvasContext.lineWidth = 1;
			this.canvasContext.fill();
			
			// draw standings
			if (drawStanding) {
				var graphInstance = this;
				var imageObj = new Image();
				imageObj.onload = function() {
					var pattern = graphInstance.canvasContext.createPattern(
							imageObj, "repeat");
					graphInstance.canvasContext.fillStyle = pattern;
					
					var standingWidth = graphInstance.CONFIG.SIDE_WIDTH - targetX;
					var standingHeight = $(graphInstance.stadiumCanvas).data("standingratioside") / 100 * (graphInstance.stadiumCanvas.height - graphInstance.CONFIG.GRAND_HEIGHT - targetY);
					var startY = graphInstance.stadiumCanvas.height / 2 - standingHeight / 2;
					var startX = graphInstance.CONFIG.SIDE_WIDTH + 	graphInstance.CONFIG.GRAND_WIDTH;
					
					graphInstance.canvasContext.fillRect(startX, 
							startY, 
							standingWidth, standingHeight);
					
				};
				imageObj.src = this.CONFIG.IMG_STANDING;
			}
		},
		
		_drawGrandLabel: function() {
			var label = $(this.stadiumCanvas).data("labelgrand");
			var textSize = this.canvasContext.measureText(label);
			
			// box
			this.canvasContext.globalAlpha = 0.8;
			this.canvasContext.fillStyle = this.CONFIG.LABEL_BACKGROUND;
			this.canvasContext.fillRect(this.CONFIG.SIDE_WIDTH + 5, 
					this.stadiumCanvas.height - this.CONFIG.GRAND_HEIGHT, textSize.width + 10, 20);
			this.canvasContext.globalAlpha = 1;
			
			// text
			this.canvasContext.fillStyle = this.CONFIG.FONT_COLOR;
			this.canvasContext.fillText(label, this.CONFIG.SIDE_WIDTH + 10, 
					this.stadiumCanvas.height - this.CONFIG.GRAND_HEIGHT + 15);
			
		},
		
		_drawSideLabel: function() {
			var label = $(this.stadiumCanvas).data("labelside");
			
			// box
			this.canvasContext.globalAlpha = 0.8;
			this.canvasContext.fillStyle = this.CONFIG.LABEL_BACKGROUND;
			this.canvasContext.fillRect(5, 
					this.stadiumCanvas.height / 2 - 15, this.CONFIG.SIDE_WIDTH, 40);
			this.canvasContext.globalAlpha = 1;
			
			// text
			this.canvasContext.fillStyle = this.CONFIG.FONT_COLOR;
			this._wrapText(this.canvasContext, label, 10, this.stadiumCanvas.height / 2, this.CONFIG.SIDE_WIDTH, 20);
		},
		
		_drawVipLabel: function() {
			var label = $(this.stadiumCanvas).data("labelvip");
			var textSize = this.canvasContext.measureText(label);
			
			var startX = this.CONFIG.SIDE_WIDTH + this.CONFIG.GRAND_WIDTH / 2 - textSize.width / 2;
			
			// box
			this.canvasContext.globalAlpha = 0.8;
			this.canvasContext.fillStyle = this.CONFIG.LABEL_BACKGROUND;
			this.canvasContext.fillRect(startX, 
					this.CONFIG.GRAND_HEIGHT - 30, textSize.width + 10, 20);
			this.canvasContext.globalAlpha = 1;
			
			// text
			this.canvasContext.fillStyle = this.CONFIG.FONT_COLOR;
			this.canvasContext.fillText(label, startX + 5, 
					this.CONFIG.GRAND_HEIGHT - 15);
			
		},
		
		_wrapText: function(context, text, x, y, maxWidth, lineHeight) {
	        var words = text.split(' ');
	        var line = '';

	        for(var n = 0; n < words.length; n++) {
	          var testLine = line + words[n] + ' ';
	          var metrics = context.measureText(testLine);
	          var testWidth = metrics.width;
	          if(testWidth > maxWidth) {
	            context.fillText(line, x, y);
	            line = words[n] + ' ';
	            y += lineHeight;
	          } else {
	            line = testLine;
	          }
	        }
	        context.fillText(line, x, y);
	      }
			
	};
	
	stadiumGraph.drawStadium("stadium");	
	
	/**
	 * fix resizing on this page (prevent overlapping of stadium with right boxes)
	 */
	$("#contentArea").css("min-width", 670);
	$(window).resize(function() {
		$("#contentArea").css("min-width", 670);
	});
});