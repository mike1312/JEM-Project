<?php
/**
 * @version 1.9
 * @package JEM
 * @copyright (C) 2013-2013 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
?>
<div id="jem" class="jem_categories_view">
	<div class="buttons">
		<?php
		echo JEMOutput::submitbutton( $this->dellink, $this->params );
		echo JEMOutput::archivebutton( $this->params, $this->task );
		?>
	</div>



	<?php if ($this->params->def( 'show_page_title', 1 )) : ?>
	<h1 class="componentheading">
		<?php echo $this->escape($this->pagetitle); ?>
	</h1>
	<?php endif; ?>

	<?php foreach ($this->rows as $row) : ?>

	<div class="floattext">
		<h2 class="jem cat<?php echo $row->id; ?>">
			<?php echo JHTML::_('link', JRoute::_($row->linktarget), $this->escape($row->catname)); ?>
		</h2>
		
		<?php if ($this->jemsettings->discatheader) {  ?>
		<div class="catimg">
			<?php //flyer

		if (empty($row->image)) {
	$jemsettings =  JEMHelper::config();
	$imgattribs['width'] = $jemsettings->imagewidth;
	$imgattribs['height'] = $jemsettings->imagehight;

	echo  JHTML::image('media/com_jem/images/noimage.png', $row->catname);
	}else{

	$cimage = JEMImage::flyercreator($row->image, 'category');
	echo JEMOutput::flyer( $row, $cimage, 'category' );

	}
	?>
    <?php  		
			// echo JHTML::_('link', JRoute::_($row->linktarget), $row->image);
			?>
			<p>
				<?php
				//		echo JText::_( 'COM_JEM_EVENTS' ).': ';
				//	echo JHTML::_('link', JRoute::_($row->linktarget), $row->assignedevents ? $row->assignedevents : '0');
				?>
			</p>

		</div>
		<?php } ?>

		<div class="catdescription cat<?php echo $row->id; ?>">
			<?php echo $row->catdescription ; ?>
			<p>
				<?php
				echo JHTML::_('link', JRoute::_($row->linktarget), $row->linktext);
				?>
				(
				<?php echo $row->assignedevents ? $row->assignedevents : '0';?>
				)
			</p>
		</div>

	</div>

	<?php 
	//only show this part if subcategries are available
	if (count($row->subcats)) :
	?>

	<div class="subcategories">
		<?php echo JText::_('COM_JEM_SUBCATEGORIES'); ?>
	</div>
	<?php
	$n = count($row->subcats);
	$i = 0;
	?>
	<div class="subcategorieslist">
		<?php foreach ($row->subcats as $sub) : ?>
		<?php if ($this->params->get('showemptychilds',1) || $sub->assignedevents): ?>
		<strong><a
			href="<?php echo JRoute::_( 'index.php?view=categoryevents&id='. $sub->slug ); ?>"><?php echo $this->escape($sub->catname); ?>
		</a> </strong> (
		<?php echo $sub->assignedevents != null ? $sub->assignedevents : 0; ?>
		)
		<?php 
		$i++;
		if ($i != $n) :
		echo ',';
		endif;
		endif;
	endforeach; ?>
	</div>

	<?php endif; ?>

	<?php endforeach; ?>

	<!--pagination-->
	<div class="pagination">
		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>

	

	<!--copyright-->

	<div class="copyright">
		<?php echo JEMOutput::footer( ); ?>
	</div>
</div>
