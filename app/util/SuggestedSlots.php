<?php

require_once __DIR__ . '/../core/Database.php';

class SuggestedSlots extends Controller {
    private $tsModel;
    private $timetableModel;

    public function __construct() {
        $this->tsModel = $this->loadModel("Timeslot");
        $this->timetableModel = $this->loadModel("Timetable");
    }

    public function findSlots()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $lecturerId = $data['lecturer_id'] ?? null;
        $class = $data['class'] ?? null;

        if (empty($lecturerId) && empty($class)) {
            http_response_code(400);
            echo json_encode(['error' => 'Either lecturer_id or class (or both) must be provided']);
            return;
        }

        try {
            $busySlots = $this->getBusySlots($lecturerId, $class);
          
            $freeSlots = $this->generateFreeSlots($busySlots);
            $suggestedSlots = $this->selectNonOverlappingSlots($freeSlots, 5);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'suggested_slots' => $suggestedSlots,
                'total_free_slots' => count($freeSlots)
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to generate suggested slots: ' . $e->getMessage()]);
        }
    }

    private function getBusySlots($lecturerId = null, $class = null)
    {
        $busySlots = [];

        if (!empty($lecturerId)) {
            $lecturerSlots = $this->timetableModel->getSlotsByLid($lecturerId);
            $busySlots = array_merge($busySlots, $lecturerSlots);
        }

        if (!empty($class)) {
            $classSlots = $this->timetableModel->getSlotsByRoom($class);
            $busySlots = array_merge($busySlots, $classSlots);
        }

        return $this->organizeBusySlotsByDay($busySlots);
    }

    private function organizeBusySlotsByDay($busySlots)
    {
        $organized = [
            'Monday' => [],
            'Tuesday' => [],
            'Wednesday' => [],
            'Thursday' => [],
            'Friday' => []
        ];

        foreach ($busySlots as $slot) {
            $slot_details = $this->tsModel->getTimeslot($slot['slot_id']);
            $day = $slot_details['day_of_week'];
            if (isset($organized[$day])) {
                $organized[$day][] = [
                    'start' => $slot_details['start_time'],
                    'end' => $slot_details['end_time']
                ];
            }
        }

        return $organized;
    }

    private function generateFreeSlots($busySlots)
    {
        $allPossibleSlots = $this->generateAllPossibleSlots();
        $freeSlots = [];

        foreach ($allPossibleSlots as $day => $daySlots) {
            foreach ($daySlots as $slot) {
                if (!$this->conflictsWithAny($slot, $busySlots[$day] ?? [])) {
                    $freeSlots[] = [
                        'day' => $day,
                        'start_time' => $slot['start'],
                        'end_time' => $slot['end'],
                        'duration' => '2 hours'
                    ];
                }
            }
        }

        return $freeSlots;
    }

    private function generateAllPossibleSlots()
    {
        $slots = [];
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        
        foreach ($days as $day) {
            $slots[$day] = [];
          
            $startHour = 6;
            $endHour = 19; 
            
            for ($hour = $startHour; $hour <= $endHour - 2; $hour++) {
                $startTime = sprintf('%02d:00', $hour);
                $endTime = sprintf('%02d:00', $hour + 2);
                
                $slots[$day][] = [
                    'start' => $startTime,
                    'end' => $endTime
                ];
            }
        }

        return $slots;
    }

    private function selectNonOverlappingSlots($freeSlots, $maxSuggestions = 5)
    {
        if (empty($freeSlots)) {
            return [];
        }

        shuffle($freeSlots);
        
        $selectedSlots = [];
        
        foreach ($freeSlots as $slot) {
            if (!$this->overlapsWithSelectedSlots($slot, $selectedSlots)) {
                $selectedSlots[] = $slot;
                
                if (count($selectedSlots) >= $maxSuggestions) {
                    break;
                }
            }
        }

        return $selectedSlots;
    }

    private function overlapsWithSelectedSlots($newSlot, $selectedSlots)
    {
        foreach ($selectedSlots as $selectedSlot) {
            if ($newSlot['day'] === $selectedSlot['day']) {
                if ($this->slotsOverlap($newSlot, $selectedSlot)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function slotsOverlap($slot1, $slot2)
    {
        $slot1Start = $this->timeToMinutes($slot1['start_time']);
        $slot1End = $this->timeToMinutes($slot1['end_time']);
        $slot2Start = $this->timeToMinutes($slot2['start_time']);
        $slot2End = $this->timeToMinutes($slot2['end_time']);

        return $slot1Start < $slot2End && $slot1End > $slot2Start;
    }

    private function conflictsWithAny($slot, $busyList)
    {
        foreach ($busyList as $busy) {
            $slotStart = $this->timeToMinutes($slot['start']);
            $slotEnd = $this->timeToMinutes($slot['end']);
            $busyStart = $this->timeToMinutes($busy['start']);
            $busyEnd = $this->timeToMinutes($busy['end']);

            if ($slotStart < $busyEnd && $slotEnd > $busyStart) {
                return true;
            }
        }
        return false;
    }

    private function timeToMinutes($time)
    {
        list($hours, $minutes) = explode(':', $time);
        return ($hours * 60) + $minutes;
    }

}