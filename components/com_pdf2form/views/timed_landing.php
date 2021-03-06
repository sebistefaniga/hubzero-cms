<h2><?= htmlentities($title) ?></h2>
<p>You have <strong><?= timeDiff($realLimit*60) ?></strong> to complete this form. There are <strong><?= $pdf->getQuestionCount() ?></strong> questions.</p>
<? if ($realLimit == $limit): ?>
	<p><em>Time will begin counting when you click 'Continue' below.</em></p>
<? else: ?>
	<p><em>Time is already running because the form is close to expiring!</em></p>
<? endif; ?>
<form action="/pdf2form" method="post">
	<fieldset>
		<input type="hidden" name="task" value="startWork" />
		<input type="hidden" name="crumb" value="<?= $dep->getCrumb() ?>" />
		<?= isset($_GET['tmpl']) ? '<input type="hidden" name="tmpl" value="'.str_replace('"', '&quot;', $_GET['tmpl']).'" />' : '' ?>
		<button type="submit">Continue</button>
	</fieldset>
</form>
