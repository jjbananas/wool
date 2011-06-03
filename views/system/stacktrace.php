<?php echo $error ?>

<ol class="pod stacktrace">
	<?php foreach ($trace as $i=>$l) { ?>
	<li>
		<span><?php echo "[$i] {$l['class']}{$l['type']}{$l['function']}({$l['argprint']})" ?></span>
		<span><?php echo "[{$l['file']}:{$l['line']}]" ?></span>
	</li>
	<?php } ?>
</ol>
