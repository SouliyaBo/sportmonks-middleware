<?php
/**
 * Template for displaying schedules (โปรแกรมการแข่งขัน)
 * Variables available: $data (API response)
 */

if (!defined('ABSPATH')) {
    exit;
}

// ตรวจสอบข้อมูล
if (!isset($data['data']) || empty($data['data'])) {
    echo '<p class="smm-no-data">ไม่มีข้อมูลตารางการแข่งขัน</p>';
    return;
}

$schedules = $data['data'];
?>

<div class="smm-schedules-container">
    <div class="smm-schedules-header">
        <h3>📅 ตารางการแข่งขัน</h3>
        <?php if (isset($data['count'])): ?>
            <span class="smm-count">(<?php echo esc_html($data['count']); ?> นัด)</span>
        <?php endif; ?>
    </div>
    
    <div class="smm-schedules-list">
        <?php 
        // โครงสร้างข้อมูล: data[] -> แต่ละตัวคือ Stage -> rounds[] -> แต่ละตัวคือ Round -> fixtures[]
        foreach ($schedules as $stage): ?>
            <?php if (isset($stage['rounds']) && is_array($stage['rounds'])): ?>
                <div class="smm-stage-section">
                    <?php if (isset($stage['name'])): ?>
                        <h3 class="smm-stage-title">🏆 <?php echo esc_html($stage['name']); ?></h3>
                    <?php endif; ?>
                    
                    <?php foreach ($stage['rounds'] as $round): ?>
                        <div class="smm-schedule-round">
                            <h4 class="smm-round-title">
                                รอบที่ <?php echo esc_html($round['name']); ?>
                                <?php if (isset($round['starting_at']) && isset($round['ending_at'])): ?>
                                    <span class="smm-round-date">
                                        (<?php 
                                        $start = new DateTime($round['starting_at']);
                                        $end = new DateTime($round['ending_at']);
                                        echo esc_html($start->format('d/m/Y')) . ' - ' . esc_html($end->format('d/m/Y'));
                                        ?>)
                                    </span>
                                <?php endif; ?>
                            </h4>
                            
                            <div class="smm-schedule-items">
                                <?php if (isset($round['fixtures']) && is_array($round['fixtures'])): ?>
                                    <?php foreach ($round['fixtures'] as $fixture): ?>
                                        <div class="smm-fixture">
                                            <div class="smm-fixture-date">
                                                <?php 
                                                if (isset($fixture['starting_at'])) {
                                                    $dt = new DateTime($fixture['starting_at']);
                                                    echo esc_html($dt->format('d/m/Y H:i'));
                                                }
                                                ?>
                                            </div>
                                            
                                            <div class="smm-fixture-teams">
                                                <?php 
                                                // หาทีมเหย้าและทีมเยือน
                                                $home_team = null;
                                                $away_team = null;
                                                if (isset($fixture['participants']) && is_array($fixture['participants'])) {
                                                    foreach ($fixture['participants'] as $participant) {
                                                        if (isset($participant['meta']['location'])) {
                                                            if ($participant['meta']['location'] === 'home') {
                                                                $home_team = $participant;
                                                            } else {
                                                                $away_team = $participant;
                                                            }
                                                        }
                                                    }
                                                    // Fallback: ใช้ลำดับแรกเป็นเหย้า ลำดับที่สองเป็นเยือน
                                                    if (!$home_team && count($fixture['participants']) > 0) {
                                                        $home_team = $fixture['participants'][0];
                                                    }
                                                    if (!$away_team && count($fixture['participants']) > 1) {
                                                        $away_team = $fixture['participants'][1];
                                                    }
                                                }
                                                ?>
                                                
                                                <div class="smm-team smm-home">
                                                    <?php if ($home_team && isset($home_team['image_path'])): ?>
                                                        <img src="<?php echo esc_url($home_team['image_path']); ?>" 
                                                             alt="<?php echo esc_attr($home_team['name']); ?>" 
                                                             class="smm-team-logo">
                                                    <?php endif; ?>
                                                    <span class="smm-team-name">
                                                        <?php echo esc_html($home_team['name'] ?? 'TBD'); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="smm-vs">VS</div>
                                                
                                                <div class="smm-team smm-away">
                                                    <?php if ($away_team && isset($away_team['image_path'])): ?>
                                                        <img src="<?php echo esc_url($away_team['image_path']); ?>" 
                                                             alt="<?php echo esc_attr($away_team['name']); ?>" 
                                                             class="smm-team-logo">
                                                    <?php endif; ?>
                                                    <span class="smm-team-name">
                                                        <?php echo esc_html($away_team['name'] ?? 'TBD'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <?php 
                                            // แสดงสกอร์ถ้ามี
                                            if (isset($fixture['scores']) && is_array($fixture['scores'])) {
                                                $home_score = null;
                                                $away_score = null;
                                                foreach ($fixture['scores'] as $score) {
                                                    if ($score['description'] === 'CURRENT') {
                                                        if ($score['score']['participant'] === 'home') {
                                                            $home_score = $score['score']['goals'];
                                                        } else {
                                                            $away_score = $score['score']['goals'];
                                                        }
                                                    }
                                                }
                                                if ($home_score !== null && $away_score !== null): ?>
                                                    <div class="smm-score">
                                                        <?php echo esc_html($home_score . ' - ' . $away_score); ?>
                                                    </div>
                                                <?php endif;
                                            }
                                            ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="smm-no-fixtures">ยังไม่มีนัดแข่งขันในรอบนี้</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <div class="smm-schedules-footer">
        <p class="smm-last-updated">
            อัปเดตล่าสุด: <?php echo current_time('d/m/Y H:i'); ?>
        </p>
    </div>
</div>

<style>
.smm-schedules-container {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
    margin: 20px 0;
}

.smm-schedules-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #3b5998;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.smm-schedules-header h3 {
    margin: 0;
    color: #3b5998;
    font-size: 1.5em;
}

.smm-count {
    background: #3b5998;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9em;
}

.smm-stage-section {
    margin-bottom: 40px;
}

.smm-stage-title {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    margin: 0 0 20px 0;
    border-radius: 6px;
    font-size: 1.3em;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.smm-schedule-round {
    margin-bottom: 30px;
}

.smm-round-title {
    background: #f0f2f5;
    padding: 10px 15px;
    margin: 0 0 15px 0;
    border-left: 4px solid #3b5998;
    color: #333;
    font-size: 1.1em;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.smm-round-date {
    font-size: 0.85em;
    color: #666;
    font-weight: normal;
}

.smm-schedule-items {
    display: grid;
    gap: 15px;
}

.smm-fixture {
    background: white;
    padding: 15px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid #eee;
    transition: all 0.3s ease;
}

.smm-fixture:hover {
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.smm-fixture-date {
    color: #888;
    font-size: 0.9em;
    margin-bottom: 10px;
    font-weight: 500;
    text-align: center;
}

.smm-fixture-teams {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 10px;
}

.smm-team {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.smm-team-logo {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.smm-team-name {
    font-weight: 600;
    color: #333;
    font-size: 0.95em;
    text-align: center;
}

.smm-vs {
    background: #3b5998;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 0.85em;
    flex-shrink: 0;
}

.smm-score {
    text-align: center;
    font-size: 1.2em;
    font-weight: bold;
    color: #3b5998;
    padding: 8px;
    background: #f0f2f5;
    border-radius: 4px;
    margin-top: 10px;
}

.smm-no-fixtures {
    text-align: center;
    padding: 20px;
    color: #999;
    font-style: italic;
}

.smm-schedules-footer {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    text-align: center;
}

.smm-last-updated {
    color: #999;
    font-size: 0.85em;
    margin: 0;
}

.smm-no-data {
    text-align: center;
    padding: 40px;
    color: #999;
    font-style: italic;
}

.smm-error {
    background: #fee;
    border: 1px solid #fcc;
    color: #c33;
    padding: 15px;
    border-radius: 6px;
    text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
    .smm-schedules-container {
        padding: 15px;
    }
    
    .smm-schedules-header h3 {
        font-size: 1.2em;
    }
    
    .smm-stage-title {
        font-size: 1.1em;
        padding: 12px 15px;
    }
    
    .smm-round-title {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .smm-fixture-teams {
        flex-direction: column;
        gap: 10px;
    }
    
    .smm-team {
        width: 100%;
    }
    
    .smm-team-logo {
        width: 35px;
        height: 35px;
    }
    
    .smm-vs {
        padding: 6px 12px;
    }
}
</style>
