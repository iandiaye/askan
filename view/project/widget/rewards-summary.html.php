<?php
use Goteo\Library\Text,
    Goteo\Model\License;

$level = (int) $this['level'] ?: 3;

$project = $this['project'];

$licenses = array();

foreach (License::getAll() as $l) {
    $licenses[$l->id] = $l;
}
?>
<div class="widget project-rewards-summary" id="rewards-summary">

    <h<?php echo $level ?> class="supertitle"><?php echo Text::get('project-rewards-supertitle'); ?></h<?php echo $level ?>>

    <div class="social">
        <h<?php echo $level + 1 ?> class="title"><?php echo Text::get('project-rewards-social_reward-title'); ?></h<?php echo $level + 1 ?>>
        <ul>
        <?php foreach ($project->social_rewards as $social) : ?>
            <li class="<?php echo $social->icon ?>">
                <h<?php echo $level + 2 ?> class="name"><?php echo htmlspecialchars($social->reward) ?></h<?php echo $level + 2 ?>
                <p><?php echo htmlspecialchars($social->description)?></p>
                <?php if (!empty($social->license) && array_key_exists($social->license, $licenses)): ?>
                <div class="license <?php echo htmlspecialchars($social->license) ?>">
                    <h<?php echo $level + 3 ?>><?php echo Text::get('regular-license'); ?></h<?php echo $level + 3 ?>>
                    <a href="<?php echo htmlspecialchars($licenses[$social->license]->url) ?>" target="_blank">
                        <strong><?php echo htmlspecialchars($licenses[$social->license]->name) ?></strong>

                    <?php if (!empty($licenses[$social->license]->description)): ?>
                    <p><?php echo htmlspecialchars($licenses[$social->license]->description) ?></p>
                    <?php endif ?>
                    </a>
                </div>
                <?php endif ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>

    <div class="individual">
        <h<?php echo $level+1 ?> class="title"><?php echo Text::get('project-rewards-individual_reward-title'); ?></h<?php echo $level+1 ?>>
        <ul>
        <?php foreach ($project->individual_rewards as $individual) : ?>
        <li class="<?php echo $individual->icon ?>">

            <div><?php echo Text::get('regular-investing'); ?> <span><?php echo $individual->amount; ?>&euro;</span></div>
            <strong><?php echo htmlspecialchars($individual->reward) ?></strong>
            <p><?php echo htmlspecialchars($individual->description) ?></p>

                    <?php if (!empty($individual->units)) : ?>
                    <strong><?php echo Text::get('project-rewards-individual_reward-limited'); ?></strong><br />
                    <?php $units = ($individual->units - $individual->taken);
                    echo Text::get('project-rewards-individual_reward-units_left', $units); ?><br />
                <?php endif; ?>
                <div><span>[<?php echo $individual->taken; ?>]</span><?php echo Text::get('project-view-metter-investors'); ?></div>

        </li>
        <?php endforeach ?>
    </div>

</div>