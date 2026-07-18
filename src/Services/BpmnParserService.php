<?php

namespace MatthewWegner\BpmnEngine\Services;

use MatthewWegner\BpmnEngine\Models\WorkflowVersion;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;
use Exception;

class BpmnParserService
{
    public function parseAndStore(WorkflowVersion $version, string $xmlString): void
    {
        $xml = new SimpleXMLElement($xmlString);

        // Register the namespaces required to read standard and Camunda tags
        $xml->registerXPathNamespace('bpmn', 'http://omg.org/spec/BPMN/20100524/MODEL');
        $xml->registerXPathNamespace('camunda', 'http://camunda.org/schema/1.0/bpmn');

        // Build a dictionary of global BPMN Messages
        $messageMap = [];
        $messages = $xml->xpath('//bpmn:message');

        if ($messages !== false) {
            foreach ($messages as $message) {
                $id = (string) $message['id'];
                $name = (string) $message['name'];
                $messageMap[$id] = $name;
            }
        }

        $process = $xml->xpath('//bpmn:process')[0] ?? null;

        if (!$process) {
            throw new Exception('Invalid BPMN file: <bpmn:process> element not found.');
        }

        DB::transaction(function () use ($version, $process) {
            // Clear existing elements if re-parsing
            $version->nodes()->delete();
            $version->edges()->delete();

            // Extract Nodes
            $nodeTypes = [
                'startEvent', 'endEvent', 
                'serviceTask', 'userTask', 
                'exclusiveGateway', 'parallelGateway'
            ];

            foreach ($nodeTypes as $type) {
                $elements = $process->xpath(".//bpmn:{$type}");

                foreach ($elements as $element) {
                    $attributes = $element->attributes();
                    $bpmnId = (string) $attributes['id'];
                    $name = (string) ($attributes['name'] ?? '');

                    // Extract the implementation key from camunda:class
                    $camundaAttrs = $element->attributes('http://camunda.org/schema/1.0/bpmn');
                    $implementation = (string) ($camundaAttrs['class'] ?? null);

                    // Intercept Message Start Events and resolve their alias
                    if ($type === 'startEvent') {
                        $msgDef = $element->xpath('.//bpmn:messageEventDefinition');
                        
                        if (!empty($msgDef)) {
                            // Extract the reference ID (e.g., 'Message_0x8b3a')
                            $messageRef = (string) $msgDef[0]['messageRef'];
                            
                            // Map it back to the actual string alias (e.g., 'custody_log_created')
                            $implementation = $messageMap[$messageRef] ?? $implementation;
                        }
                    }

                    $version->nodes()->create([
                        'bpmn_element_id' => $bpmnId,
                        'type'            => $type,
                        'name'            => $name ?: null,
                        'implementation'  => $implementation,
                    ]);
                }
            }

            // Extract Edges (Sequence Flows)
            $flows = $process->xpath('.//bpmn:sequenceFlow');

            foreach ($flows as $flow) {
                $attributes = $flow->attributes();
                
                $conditionElement = $flow->xpath('.//bpmn:conditionExpression');
                $condition = !empty($conditionElement) ? (string) $conditionElement[0] : null;

                $version->edges()->create([
                    'bpmn_element_id'      => (string) $attributes['id'],
                    'source_node_id'       => (string) $attributes['sourceRef'],
                    'target_node_id'       => (string) $attributes['targetRef'],
                    'condition_expression' => $condition,
                ]);
            }
        });
    }
}