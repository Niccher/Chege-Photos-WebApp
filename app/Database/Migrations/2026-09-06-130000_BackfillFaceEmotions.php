<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillFaceEmotions extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tbl_face_encodings')) {
            // Ensure emotion column exists
            if (!$this->db->fieldExists('emotion', 'tbl_face_encodings')) {
                $this->forge->addColumn('tbl_face_encodings', [
                    'emotion' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 50,
                        'null'       => true,
                        'after'      => 'gender',
                    ],
                ]);
            }

            // Create index for emotion search if not exists
            try {
                $indexes = $this->db->getIndexData('tbl_face_encodings');
                $hasIndex = false;
                foreach ($indexes as $idx) {
                    if ($idx->name === 'idx_face_emotion') {
                        $hasIndex = true;
                        break;
                    }
                }
                if (!$hasIndex) {
                    $this->db->query("CREATE INDEX `idx_face_emotion` ON `tbl_face_encodings` (`emotion`)");
                }
            } catch (\Exception $e) {
                log_message('warning', 'Failed creating index idx_face_emotion: ' . $e->getMessage());
            }

            // Backfill emotion for existing faces with landmarks
            try {
                $faces = $this->db->table('tbl_face_encodings')
                    ->select('id, landmark_left_eye_x, landmark_left_eye_y, landmark_right_eye_x, landmark_right_eye_y, landmark_left_mouth_x, landmark_left_mouth_y, landmark_right_mouth_x, landmark_right_mouth_y')
                    ->where('emotion IS NULL')
                    ->orWhere('emotion', '')
                    ->get()
                    ->getResultArray();

                foreach ($faces as $f) {
                    $emotion = 'Neutral 🙂';
                    if ($f['landmark_left_eye_x'] !== null && $f['landmark_right_eye_x'] !== null) {
                        $ipd = hypot(
                            (float)$f['landmark_right_eye_x'] - (float)$f['landmark_left_eye_x'],
                            (float)$f['landmark_right_eye_y'] - (float)$f['landmark_left_eye_y']
                        );

                        if ($ipd > 0 && $f['landmark_left_mouth_x'] !== null && $f['landmark_right_mouth_x'] !== null) {
                            $mouthWidth = hypot(
                                (float)$f['landmark_right_mouth_x'] - (float)$f['landmark_left_mouth_x'],
                                (float)$f['landmark_right_mouth_y'] - (float)$f['landmark_left_mouth_y']
                            );
                            $ratio = $mouthWidth / $ipd;
                            if ($ratio >= 0.78) {
                                $emotion = 'Smiling 😊';
                            } elseif ($ratio <= 0.58) {
                                $emotion = 'Serious/Neutral 😐';
                            }
                        }
                    }

                    $this->db->table('tbl_face_encodings')
                        ->where('id', $f['id'])
                        ->update(['emotion' => $emotion]);
                }
            } catch (\Exception $e) {
                log_message('warning', 'Failed backfilling face emotions: ' . $e->getMessage());
            }
        }
    }

    public function down()
    {
        // Safe no-op
    }
}
