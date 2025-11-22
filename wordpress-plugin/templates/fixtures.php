<?php
/**
 * Template: Fixtures
 * Variables: $data (array from API)
 */

if (!defined('ABSPATH')) exit;

$fixtures = isset($data['data']) ? $data['data'] : array();
?>

<div class="smm-fixtures">
    <h3 class="smm-title">📅 ตารางแข่งขัน</h3>
    
    <?php if (empty($fixtures)): ?>
        <p class="smm-no-data">ไม่มีตารางแข่งขัน</p>
    <?php else: ?>
        <div class="smm-fixtures-list">
            <?php foreach ($fixtures as $fixture): ?>
                <div class="smm-fixture-card">
                    <div class="smm-fixture-header">
                        <span class="smm-league"><?php echo esc_html($fixture['league']['name']); ?></span>
                        <?php if (!empty($fixture['round'])): ?>
                            <span class="smm-round"><?php echo esc_html($fixture['round']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="smm-fixture-body">
                        <div class="smm-team">
                            <?php if (!empty($fixture['homeTeam']['logo'])): ?>
                                <img src="<?php echo esc_url($fixture['homeTeam']['logo']); ?>" 
                                     alt="<?php echo esc_attr($fixture['homeTeam']['name']); ?>" 
                                     class="smm-team-logo">
                            <?php endif; ?>
                            <span class="smm-team-name"><?php echo esc_html($fixture['homeTeam']['name']); ?></span>
                        </div>
                        
                        <div class="smm-vs">VS</div>
                        
                        <div class="smm-team">
                            <?php if (!empty($fixture['awayTeam']['logo'])): ?>
                                <img src="<?php echo esc_url($fixture['awayTeam']['logo']); ?>" 
                                     alt="<?php echo esc_attr($fixture['awayTeam']['name']); ?>" 
                                     class="smm-team-logo">
                            <?php endif; ?>
                            <span class="smm-team-name"><?php echo esc_html($fixture['awayTeam']['name']); ?></span>
                        </div>
                    </div>
                    
                    <div class="smm-fixture-footer">
                        <span class="smm-datetime">
                            <?php 
                            $datetime = new DateTime($fixture['startTime']);
                            echo $datetime->format('d M Y, H:i'); 
                            ?>
                        </span>
                        <?php if (!empty($fixture['venue'])): ?>
                            <span class="smm-venue">📍 <?php echo esc_html($fixture['venue']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
