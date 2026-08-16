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

  function dataFloat(el, key) {
    return parseFloat(el.dataset[key]) || 0;
  }

  document.addEventListener("DOMContentLoaded", function () {
    if (!document.getElementById("stadium")) {
      return;
    }

    const stadiumGraph = {
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
        IMG_STANDING: "img/standing.png",
      },

      stadiumCanvas: null,
      canvasContext: null,

      drawStadium: function (elementId) {
        this.stadiumCanvas = document.getElementById(elementId);
        this.canvasContext = this.stadiumCanvas.getContext("2d");
        this.canvasContext.font = "normal 12px Arial";

        this.canvasContext.fillStyle = this.CONFIG.MAX_SIZE_STYLE;

        this._drawGrandTop(100);
        this._drawGrandBottom(100);
        this._drawSideLeft(100);
        this._drawSideRight(100);

        const graphInstance = this;
        const ratioGrand = dataFloat(this.stadiumCanvas, "ratiogrand");
        const ratioSide = dataFloat(this.stadiumCanvas, "ratioside");

        const imageObj = new Image();
        imageObj.onload = function () {
          const pattern = graphInstance.canvasContext.createPattern(
            imageObj,
            "repeat"
          );
          graphInstance.canvasContext.fillStyle = pattern;

          graphInstance._drawGrandTop(ratioGrand);
          graphInstance._drawGrandBottom(ratioGrand, true);

          graphInstance._drawSideRight(ratioSide, true);
          graphInstance._drawSideLeft(ratioSide, true);

          const ratioVip = dataFloat(graphInstance.stadiumCanvas, "ratiovip");
          graphInstance._drawVip(ratioVip);
        };
        imageObj.src = this.CONFIG.IMG_SEAT;
      },

      _drawGrandTop: function (perCent) {
        const targetY =
          this.CONFIG.GRAND_HEIGHT - (perCent / 100) * this.CONFIG.GRAND_HEIGHT;

        this.canvasContext.fillRect(
          this.CONFIG.SIDE_WIDTH,
          targetY,
          this.CONFIG.GRAND_WIDTH,
          this.CONFIG.GRAND_HEIGHT - targetY
        );
      },

      _drawGrandBottom: function (perCent, drawStanding) {
        const targetY =
          this.CONFIG.GRAND_HEIGHT - (perCent / 100) * this.CONFIG.GRAND_HEIGHT;

        const startX = this.CONFIG.SIDE_WIDTH;
        const startY = this.stadiumCanvas.height - this.CONFIG.GRAND_HEIGHT;

        this.canvasContext.fillRect(
          startX,
          startY,
          this.CONFIG.GRAND_WIDTH,
          this.CONFIG.GRAND_HEIGHT - targetY
        );

        if (drawStanding) {
          const graphInstance = this;
          const imageObj = new Image();
          imageObj.onload = function () {
            const pattern = graphInstance.canvasContext.createPattern(
              imageObj,
              "repeat"
            );
            graphInstance.canvasContext.fillStyle = pattern;

            const standingWidth =
              (dataFloat(graphInstance.stadiumCanvas, "standingratiogrand") /
                100) *
              graphInstance.CONFIG.GRAND_WIDTH;
            const standingStartX =
              startX + graphInstance.CONFIG.GRAND_WIDTH / 2 - standingWidth / 2;
            graphInstance.canvasContext.fillRect(
              standingStartX,
              startY,
              standingWidth,
              graphInstance.CONFIG.GRAND_HEIGHT - targetY
            );

            graphInstance._drawGrandLabel();
          };
          imageObj.src = this.CONFIG.IMG_STANDING;
        }
      },

      _drawVip: function (perCent) {
        const maxWidth = 0.9 * this.CONFIG.GRAND_WIDTH;
        const actualWidth = (maxWidth * perCent) / 100;
        const startX =
          this.CONFIG.SIDE_WIDTH + this.CONFIG.GRAND_WIDTH / 2 - actualWidth / 2;
        const startY = this.CONFIG.GRAND_HEIGHT - this.CONFIG.VIP_HEIGHT;

        const graphInstance = this;
        const imageObj = new Image();
        imageObj.onload = function () {
          const pattern = graphInstance.canvasContext.createPattern(
            imageObj,
            "repeat"
          );
          graphInstance.canvasContext.fillStyle = pattern;
          graphInstance.canvasContext.fillRect(
            startX,
            startY,
            actualWidth,
            graphInstance.CONFIG.VIP_HEIGHT
          );

          graphInstance._drawVipLabel();
        };
        imageObj.src = this.CONFIG.IMG_VIP;
      },

      _drawSideLeft: function (perCent, drawStanding) {
        this.canvasContext.beginPath();

        const targetY =
          this.CONFIG.SIDE_WIDTH - (perCent / 100) * this.CONFIG.SIDE_WIDTH;
        this.canvasContext.moveTo(this.CONFIG.SIDE_WIDTH, targetY);

        const targetX =
          this.CONFIG.SIDE_WIDTH - (perCent / 100) * this.CONFIG.SIDE_WIDTH;
        this.canvasContext.quadraticCurveTo(
          targetX + 10,
          targetY,
          targetX,
          this.CONFIG.GRAND_HEIGHT
        );

        this.canvasContext.lineTo(
          targetX,
          this.stadiumCanvas.height - this.CONFIG.GRAND_HEIGHT
        );

        this.canvasContext.quadraticCurveTo(
          targetX + 10,
          this.stadiumCanvas.height - targetY,
          this.CONFIG.SIDE_WIDTH,
          this.stadiumCanvas.height - targetY
        );

        this.canvasContext.closePath();
        this.canvasContext.lineWidth = 1;
        this.canvasContext.fill();

        if (drawStanding) {
          const graphInstance = this;
          const imageObj = new Image();
          imageObj.onload = function () {
            const pattern = graphInstance.canvasContext.createPattern(
              imageObj,
              "repeat"
            );
            graphInstance.canvasContext.fillStyle = pattern;

            const standingWidth = graphInstance.CONFIG.SIDE_WIDTH - targetX;
            const standingHeight =
              (dataFloat(graphInstance.stadiumCanvas, "standingratioside") /
                100) *
              (graphInstance.stadiumCanvas.height -
                graphInstance.CONFIG.GRAND_HEIGHT -
                targetY);
            const startY =
              graphInstance.stadiumCanvas.height / 2 - standingHeight / 2;

            graphInstance.canvasContext.fillRect(
              targetX,
              startY,
              standingWidth,
              standingHeight
            );

            graphInstance._drawSideLabel();
          };
          imageObj.src = this.CONFIG.IMG_STANDING;
        }
      },

      _drawSideRight: function (perCent, drawStanding) {
        this.canvasContext.beginPath();

        const targetY =
          this.CONFIG.SIDE_WIDTH - (perCent / 100) * this.CONFIG.SIDE_WIDTH;
        this.canvasContext.moveTo(
          this.CONFIG.SIDE_WIDTH + this.CONFIG.GRAND_WIDTH,
          targetY
        );

        const targetX =
          this.CONFIG.SIDE_WIDTH - (perCent / 100) * this.CONFIG.SIDE_WIDTH;
        this.canvasContext.quadraticCurveTo(
          this.stadiumCanvas.width - targetX - 10,
          targetX,
          this.stadiumCanvas.width - targetX,
          this.CONFIG.GRAND_HEIGHT
        );

        this.canvasContext.lineTo(
          this.stadiumCanvas.width - targetX,
          this.stadiumCanvas.height - this.CONFIG.GRAND_HEIGHT
        );

        this.canvasContext.quadraticCurveTo(
          this.stadiumCanvas.width - targetX - 10,
          this.stadiumCanvas.height - targetY,
          this.CONFIG.SIDE_WIDTH + this.CONFIG.GRAND_WIDTH,
          this.stadiumCanvas.height - targetY
        );

        this.canvasContext.closePath();
        this.canvasContext.lineWidth = 1;
        this.canvasContext.fill();

        if (drawStanding) {
          const graphInstance = this;
          const imageObj = new Image();
          imageObj.onload = function () {
            const pattern = graphInstance.canvasContext.createPattern(
              imageObj,
              "repeat"
            );
            graphInstance.canvasContext.fillStyle = pattern;

            const standingWidth = graphInstance.CONFIG.SIDE_WIDTH - targetX;
            const standingHeight =
              (dataFloat(graphInstance.stadiumCanvas, "standingratioside") /
                100) *
              (graphInstance.stadiumCanvas.height -
                graphInstance.CONFIG.GRAND_HEIGHT -
                targetY);
            const startY =
              graphInstance.stadiumCanvas.height / 2 - standingHeight / 2;
            const startX =
              graphInstance.CONFIG.SIDE_WIDTH + graphInstance.CONFIG.GRAND_WIDTH;

            graphInstance.canvasContext.fillRect(
              startX,
              startY,
              standingWidth,
              standingHeight
            );
          };
          imageObj.src = this.CONFIG.IMG_STANDING;
        }
      },

      _drawGrandLabel: function () {
        const label = this.stadiumCanvas.dataset.labelgrand;
        const textSize = this.canvasContext.measureText(label);

        this.canvasContext.globalAlpha = 0.8;
        this.canvasContext.fillStyle = this.CONFIG.LABEL_BACKGROUND;
        this.canvasContext.fillRect(
          this.CONFIG.SIDE_WIDTH + 5,
          this.stadiumCanvas.height - this.CONFIG.GRAND_HEIGHT,
          textSize.width + 10,
          20
        );
        this.canvasContext.globalAlpha = 1;

        this.canvasContext.fillStyle = this.CONFIG.FONT_COLOR;
        this.canvasContext.fillText(
          label,
          this.CONFIG.SIDE_WIDTH + 10,
          this.stadiumCanvas.height - this.CONFIG.GRAND_HEIGHT + 15
        );
      },

      _drawSideLabel: function () {
        const label = this.stadiumCanvas.dataset.labelside;

        this.canvasContext.globalAlpha = 0.8;
        this.canvasContext.fillStyle = this.CONFIG.LABEL_BACKGROUND;
        this.canvasContext.fillRect(
          5,
          this.stadiumCanvas.height / 2 - 15,
          this.CONFIG.SIDE_WIDTH,
          40
        );
        this.canvasContext.globalAlpha = 1;

        this.canvasContext.fillStyle = this.CONFIG.FONT_COLOR;
        this._wrapText(
          this.canvasContext,
          label,
          10,
          this.stadiumCanvas.height / 2,
          this.CONFIG.SIDE_WIDTH,
          20
        );
      },

      _drawVipLabel: function () {
        const label = this.stadiumCanvas.dataset.labelvip;
        const textSize = this.canvasContext.measureText(label);

        const startX =
          this.CONFIG.SIDE_WIDTH +
          this.CONFIG.GRAND_WIDTH / 2 -
          textSize.width / 2;

        this.canvasContext.globalAlpha = 0.8;
        this.canvasContext.fillStyle = this.CONFIG.LABEL_BACKGROUND;
        this.canvasContext.fillRect(
          startX,
          this.CONFIG.GRAND_HEIGHT - 30,
          textSize.width + 10,
          20
        );
        this.canvasContext.globalAlpha = 1;

        this.canvasContext.fillStyle = this.CONFIG.FONT_COLOR;
        this.canvasContext.fillText(
          label,
          startX + 5,
          this.CONFIG.GRAND_HEIGHT - 15
        );
      },

      _wrapText: function (context, text, x, y, maxWidth, lineHeight) {
        const words = text.split(" ");
        let line = "";

        for (let n = 0; n < words.length; n++) {
          const testLine = line + words[n] + " ";
          const metrics = context.measureText(testLine);
          const testWidth = metrics.width;
          if (testWidth > maxWidth) {
            context.fillText(line, x, y);
            line = words[n] + " ";
            y += lineHeight;
          } else {
            line = testLine;
          }
        }
        context.fillText(line, x, y);
      },
    };

    stadiumGraph.drawStadium("stadium");

    /* fix resizing on this page (prevent overlapping of stadium with right boxes) */
    const contentArea = document.getElementById("contentArea");
    function fixMinWidth() {
      if (contentArea) contentArea.style.minWidth = "670px";
    }
    fixMinWidth();
    window.addEventListener("resize", fixMinWidth);
  });
})();
