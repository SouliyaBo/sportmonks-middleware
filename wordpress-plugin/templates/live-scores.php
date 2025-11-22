<?php
/**
 * Template: Live Scores
 * Variables: $data (array from API)
 */

if (!defined('ABSPATH')) exit;

$matches = isset($data['data']) ? $data['data'] : array();
?>

<div class="smm-live-scores">
    <h3 class="smm-title">⚽ คะแนนสด</h3>
    
    <?php if (empty($matches)): ?>
        <p class="smm-no-data">ไม่มีการแข่งขันในขณะนี้</p>
    <?php else: ?>
        <div class="smm-matches-list">
            <?php foreach ($matches as $match): ?>
                <div class="smm-match-card <?php echo esc_attr($match['status']['short']); ?>">
                    <div class="smm-match-header">
                        <span class="smm-league"><?php echo esc_html($match['league']['name']); ?></span>
                        <span class="smm-status"><?php echo esc_html($match['status']['name']); ?></span>
                    </div>
                    
                    <div class="smm-match-body">
                        <div class="smm-team smm-home">
                            <?php if (!empty($match['homeTeam']['logo'])): ?>
                                <img src="<?php echo esc_url($match['homeTeam']['logo']); ?>" 
                                     alt="<?php echo esc_attr($match['homeTeam']['name']); ?>" 
                                     class="smm-team-logo">
                            <?php endif; ?>
                            <span class="smm-team-name"><?php echo esc_html($match['homeTeam']['name']); ?></span>
                        </div>
                        
                        <div class="smm-score">
                            <span class="smm-score-home"><?php echo esc_html($match['score']['home']); ?></span>
                            <span class="smm-score-separator">-</span>
                            <span class="smm-score-away"><?php echo esc_html($match['score']['away']); ?></span>
                        </div>
                        
                        <div class="smm-team smm-away">
                            <?php if (!empty($match['awayTeam']['logo'])): ?>
                                <img src="<?php echo esc_url($match['awayTeam']['logo']); ?>" 
                                     alt="<?php echo esc_attr($match['awayTeam']['name']); ?>" 
                                     class="smm-team-logo">
                            <?php endif; ?>
                            <span class="smm-team-name"><?php echo esc_html($match['awayTeam']['name']); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($match['status']['minute'])): ?>
                        <div class="smm-match-footer">
                            <span class="smm-minute"><?php echo esc_html($match['status']['minute']); ?>'</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
