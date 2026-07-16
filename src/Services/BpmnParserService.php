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