<?php
/**
 * Template: Standings
 * Variables: $data (array from API)
 */

if (!defined('ABSPATH')) exit;

$standings = isset($data['data']) ? $data['data'] : array();
?>

<div class="smm-standings">
    <h3 class="smm-title">🏆 ตารางคะแนน</h3>
    
    <?php if (empty($standings)): ?>
        <p class="smm-no-data">ไม่มีตารางคะแนน</p>
    <?php else: ?>
        <div class="smm-standings-table-wrapper">
            <table class="smm-standings-table">
                <thead>
                    <tr>
                        <th class="smm-col-position">#</th>
                        <th class="smm-col-team">ทีม</th>
                        <th class="smm-col-played">แข่ง</th>
                        <th class="smm-col-won">ชนะ</th>
                        <th class="smm-col-draw">เสมอ</th>
                        <th class="smm-col-lost">แพ้</th>
                        <th class="smm-col-gd">ผลต่าง</th>
                        <th class="smm-col-points">คะแนน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($standings as $standing): ?>
                        <tr class="smm-standing-row">
                            <td class="smm-col-position">
                                <span class="smm-position"><?php echo esc_html($standing['position']); ?></span>
                            </td>
                            <td class="smm-col-team">
                                <div class="smm-team-info">
                                    <?php if (!empty($standing['team']['logo'])): ?>
                                        <img src="<?php echo esc_url($standing['team']['logo']); ?>" 
                                             alt="<?php echo esc_attr($standing['team']['name']); ?>" 
                                             class="smm-team-logo-small">
                                    <?php endif; ?>
                                    <span class="smm-team-name"><?php echo esc_html($standing['team']['name']); ?></span>
                                </div>
                            </td>
                            <td class="smm-col-played"><?php echo esc_html($standing['stats']['played']); ?></td>
                            <td class="smm-col-won"><?php echo esc_html($standing['stats']['won']); ?></td>
                            <td class="smm-col-draw"><?php echo esc_html($standing['stats']['draw']); ?></td>
                            <td class="smm-col-lost"><?php echo esc_html($standing['stats']['lost']); ?></td>
                            <td class="smm-col-gd">
                                <?php 
                                $gd = $standing['stats']['goalDifference'];
                                $class = $gd > 0 ? 'smm-positive' : ($gd < 0 ? 'smm-negative' : '');
                                ?>
                                <span class="<?php echo $class; ?>">
                                    <?php echo $gd > 0 ? '+' : ''; ?><?php echo esc_html($gd); ?>
                                </span>
                            </td>
                            <td class="smm-col-points">
                                <strong><?php echo esc_html($standing['stats']['points']); ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
