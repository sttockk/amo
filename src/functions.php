<?php

function task1($amoV4Client)
{
    $leads = $amoV4Client->GETRequestApi('leads', [
        'filter[statuses][0][pipeline_id]' => 6969714,
        'filter[statuses][0][status_id]' => 58563422,
    ]);

    if (!is_null($leads)) {
        $pipelineLeads = $leads["_embedded"]["leads"];

        foreach ($pipelineLeads as $lead) {
            if ($lead['price'] > 5000) {
                $arr = [
                    'id' => $lead['id'],
                    'name' => $lead['name'],
                    'price' => $lead['price'],
                    'status_id' => 58563426,
                    'pipeline_id' => $lead['pipeline_id'],
                    'custom_fields_values' => $lead['custom_fields_values'],
                ];
                $amoV4Client->POSTRequestApi("leads", [
                    $arr
                ], "PATCH");
            }
        }
    }
}


function task2($amoV4Client)
{
    $leads = $amoV4Client->GETRequestApi('leads', [
        'filter[statuses][0][pipeline_id]' => 6969714,
        'filter[statuses][0][status_id]' => 58563430,
        'filter[price]' => 4999,
        'with' => 'contacts'
    ]);

    if (!is_null($leads)) {
        $pipelineLead = $leads["_embedded"]["leads"];

        foreach ($pipelineLead as $lead) {
            $notes = $amoV4Client->GETRequestApi("leads/{$lead["id"]}/notes");

            $tasks = $amoV4Client->GETRequestApi("tasks", [
                'filter[entity_id]' => $lead["id"],
            ]);

            $arrLead = [
                'name' => $lead['name'],
                'price' => $lead['price'],
                'status_id' => 58563426,
                'pipeline_id' => $lead['pipeline_id'],
                'created_by' => $lead['created_by'],
                'updated_by' => $lead['updated_by'],
                'created_at' => $lead['created_at'],
                "updated_at" => $lead['updated_at'],
                'custom_fields_values' => $lead['custom_fields_values'],
                '_embedded' => [
                    'companies' => [
                        [
                            'id' => $lead['_embedded']['companies'][0]['id']
                        ]
                    ],
                    'contacts' => [
                        [
                            'id' => $lead['_embedded']['contacts'][0]['id']
                        ]
                    ],
                ],
            ];

            $addLead = $amoV4Client->POSTRequestApi('leads', [
                $arrLead
            ]);

            $newLeadId = $addLead["_embedded"]["leads"][0]["id"];

            if (!is_null($notes)) {
                foreach ($notes["_embedded"]["notes"] as $note) {
                    $paramNote = 'leads';
                    $arrNote = [
                        "entity_id" => $newLeadId,
                        "created_by" => $note['created_by'],
                        'updated_by' => $note['updated_by'],
                        "note_type" => $note['note_type'],
                        'created_at' => $note['created_at'],
                        "updated_at" => $note['updated_at'],
                        "params" => $note['params'],
                    ];

                    $amoV4Client->POSTRequestApi("{$paramNote}/notes", [
                        $arrNote
                    ]);
                }

                if (!is_null($tasks)) {
                    foreach ($tasks["_embedded"]["tasks"] as $task) {
                        $arrTask = [
                            'text' => $task['text'],
                            'complete_till' => $task['complete_till'],
                            "entity_type" => $task['entity_type'],
                            "result" => $task['result'],
                            "task_type_id" => $task['task_type_id'],
                            "created_by" => $task['created_by'],
                            'updated_by' => $task['updated_by'],
                            'created_at' => $task['created_at'],
                            "updated_at" => $task['updated_at'],
                            "entity_id" => $newLeadId,
                        ];

                        $amoV4Client->POSTRequestApi('tasks', [
                            $arrTask
                        ]);
                    }
                }
            }
        }
    }
}