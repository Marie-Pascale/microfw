<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="utf-8">


		<title><?php echo $this->title?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="<?php echo $this->description?>">

	</head>

	<body>
		<header>
			<div class="container">
				<div class="logo">
					<?php echo $this->i18n['key'] ?>
				</div>

			</div>
<?php foreach ($this->i18n->getAcceptedLanguages() as $lang) { ?>
			<p><?php if ($lang!=$this->i18n) { ?><a href="?_lang=<?php echo $lang ?>" class="language"><?php } ?><?php echo $this->i18n[$lang] ?><?php if ($lang!=$this->i18n) { ?></a><?php } ?></p>
<?php } ?>
		</header>