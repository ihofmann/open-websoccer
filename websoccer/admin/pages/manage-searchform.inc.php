<?php
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


?>
<div class="accordion" id="searchFrm">
	<div class="accordion-group">
		<div class="accordion-heading">
			<a class="accordion-toggle" data-toggle="collapse"
				data-parent="#searchFrm" href="#collapseOne"
				title="<?php echo $i18n->getMessage("manage_search_collapse"); ?>"> <i class="icon-filter"></i>
				<?php echo $i18n->getMessage("manage_search_title"); ?>
			</a>
		</div>
		<div id="collapseOne" class="accordion-body collapse <?php if ($openSearchForm) echo "in"?>">
			<div class="accordion-inner">
				<form class="form-horizontal" name="frmSearch"
					action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
					<input type="hidden" name="site" value="<?php echo $site; ?>"> 
					<input type="hidden" name="entity" value="<?php echo $entity; ?>">

					<?php
					foreach ($filterFields as $filterFieldId => $filterFieldInfo) {
						if ($filterFieldInfo["type"] !== "timestamp" && $filterFieldInfo["type"] !== "date") {
							echo FormBuilder::createFormGroup($i18n, $filterFieldId, $filterFieldInfo, $filterFieldInfo["value"], "");
						}
					}
					?>
					
					<div class="control-group">
						<div class="controls">
							<button type="submit" class="btn btn-primary"><?php echo $i18n->getMessage("button_search"); ?></button>
							<a href="?site=<?php echo $site; ?>&entity=<?php echo $entity; ?>&filterreset=1" class="btn"><?php echo $i18n->getMessage("button_reset"); ?></a>
						</div>
					</div>
				</form>					
			
			</div>

		</div>
	</div>
</div>
