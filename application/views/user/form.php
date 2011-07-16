	<div class="r pagecontent">
		<div class="pageTop withBorder">
			<div class="pageTitle l">Create a new user</div>
			<div class="pageDesc r">Create a new user here.</div>
			<div class="clear"></div>
		</div><!-- pageTop -->
		
		<?php echo $form->startform(); ?>
			<table class="formcontainer">
				<tr>
					<td><?php echo $form->firstname->label(); ?></td>
					<td><?php echo $form->firstname->element(); ?>
                        <span class="form-error"><?php echo $form->firstname->error(); ?></span></td>
				</tr>
				<tr>
					<td><?php echo $form->lastname->label(); ?></td>
					<td><?php echo $form->lastname->element(); ?>
					    <span class="form-error"><?php echo $form->lastname->error(); ?></span></td>
				</tr>
				<tr>
					<td><?php echo $form->email->label(); ?></td>
					<td><?php echo $form->email->element(); ?>
					    <span class="form-error"><?php echo $form->email->error(); ?></span></td>
				</tr>
				<tr>
					<td><?php echo $form->role_id->label(); ?></td>
					<td><?php echo $form->role_id->element(); ?></td>
				</tr>
                <tr>
                    <td><?php echo $form->batch_id->label(); ?></td>
                    <td>
                        <?php echo $form->batch_id->element(); ?>
                        <p class="tip">You can select multiple batches.</p>
                    </td>
                </tr>
                <tr>
                    <td><label for="course">Select course</label></td>
                    <td>
                        <select name="" id="course" multiple>
                            <option value="">Course 1</option>
                            <option value="">Course 1</option>
                            <option value="">Course 1</option>
                            <option value="">Course 1</option>
                        </select>
                        <p class="tip">You can select multiple courses.</p>
                    </td>
                </tr>
				<tr>
					<td></td>
					<td><?php echo $form->save->element(); ?></td>
				</tr>
			</table>
		<?php echo $form->endForm(); ?>
		
	</div><!-- pagecontent -->
	
	<div class="clear"></div>