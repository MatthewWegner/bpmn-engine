<?php

namespace MatthewWegner\BpmnEngine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use MatthewWegner\BpmnEngine\Models\WorkflowDefinition;
use MatthewWegner\BpmnEngine\Services\BpmnParserService;

class DemoCommand extends Command
{
    protected $signature = 'bpmn:demo';
    protected $description = 'Scaffold a complete, working Order Processing demo workflow';

    public function handle(BpmnParserService $parser)
    {
        $this->info('Scaffolding BPMN Demo Environment...');

        // Scaffold the Demo Activity
        $this->publishStub('demo-activity.stub', app_path('Workflows/Activities/DemoGenerateInvoiceActivity.php'));
        $this->registerInConfig('activities', 'demo_generate_invoice', 'App\Workflows\Activities\DemoGenerateInvoiceActivity');

        // Scaffold the Demo Trigger
        $this->publishStub('demo-trigger.stub', app_path('Events/DemoOrderPlaced.php'));
        $this->registerInConfig('triggers', 'demo_order_placed', 'App\Events\DemoOrderPlaced');

        // Generate the Database Diagram
        $this->seedDemoWorkflow($parser);

        $this->info('Demo successfully installed!');
        $this->line('You can view the workflow at /bpmn/workflows');
        $this->line('To run the demo, open a Tinker session and fire: \App\Events\DemoOrderPlaced::dispatch("ORD-100", 1500, "Jane Doe");');
    }

    protected function publishStub(string $stubName, string $targetPath)
    {
        $stubPath = __DIR__ . '/../../../stubs/' . $stubName;
        
        if (!File::exists(dirname($targetPath))) {
            File::makeDirectory(dirname($targetPath), 0755, true);
        }

        File::copy($stubPath, $targetPath);
        $this->line("Created: {$targetPath}");
    }

    protected function registerInConfig(string $arrayType, string $key, string $className)
    {
        $configPath = config_path('bpmn-engine.php');
        if (!File::exists($configPath)) return;

        $configContents = File::get($configPath);
        $newLine = "        '{$key}' => \\{$className}::class,";

        if (!str_contains($configContents, $newLine)) {
            $pattern = "/('{$arrayType}'\s*=>\s*\[)/";
            $replacement = "$1\n" . $newLine;
            $newContents = preg_replace($pattern, $replacement, $configContents, 1);
            File::put($configPath, $newContents);
        }
    }

    protected function seedDemoWorkflow(BpmnParserService $parser)
    {
        $def = WorkflowDefinition::firstOrCreate(
            ['key' => 'demo-order-processing'],
            ['name' => 'Demo: Order Processing']
        );

        $xml = $this->getDemoXml();

        $version = $def->versions()->create([
            'version'   => $def->versions()->max('version') + 1,
            'bpmn_xml'  => $xml,
            'is_active' => true,
        ]);

        $parser->parseAndStore($version, $xml);
        $this->line("Seeded Workflow Diagram: Demo: Order Processing");
    }

    protected function getDemoXml(): string
    {
        // A pre-built XML string featuring a Message Start, Exclusive Gateway, User Task, Service Task, and Parallel layout.
        return '<?xml version="1.0" encoding="UTF-8"?>
        <bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" 
                          xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" 
                          xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" 
                          xmlns:camunda="http://camunda.org/schema/1.0/bpmn" 
                          xmlns:di="http://www.omg.org/spec/DD/20100524/DI" 
                          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                          id="Definitions_1" 
                          targetNamespace="http://bpmn.io/schema/bpmn">
          <bpmn:process id="Process_1" isExecutable="true">
            <bpmn:startEvent id="StartEvent_1" name="Order Placed">
              <bpmn:outgoing>Flow_1</bpmn:outgoing>
              <bpmn:messageEventDefinition messageRef="Message_Demo" />
            </bpmn:startEvent>
            <bpmn:exclusiveGateway id="Gateway_VIP" name="Order &gt; $1000?">
              <bpmn:incoming>Flow_1</bpmn:incoming>
              <bpmn:outgoing>Flow_HighValue</bpmn:outgoing>
              <bpmn:outgoing>Flow_Standard</bpmn:outgoing>
            </bpmn:exclusiveGateway>
            <bpmn:userTask id="Task_ManualReview" name="Manager Review">
              <bpmn:incoming>Flow_HighValue</bpmn:incoming>
              <bpmn:outgoing>Flow_2</bpmn:outgoing>
            </bpmn:userTask>
            <bpmn:exclusiveGateway id="Gateway_Merge">
              <bpmn:incoming>Flow_2</bpmn:incoming>
              <bpmn:incoming>Flow_Standard</bpmn:incoming>
              <bpmn:outgoing>Flow_3</bpmn:outgoing>
            </bpmn:exclusiveGateway>
            <bpmn:serviceTask id="Task_Invoice" name="Generate Invoice" camunda:class="demo_generate_invoice">
              <bpmn:incoming>Flow_3</bpmn:incoming>
              <bpmn:outgoing>Flow_End</bpmn:outgoing>
            </bpmn:serviceTask>
            <bpmn:endEvent id="EndEvent_1">
              <bpmn:incoming>Flow_End</bpmn:incoming>
            </bpmn:endEvent>
            <bpmn:sequenceFlow id="Flow_1" sourceRef="StartEvent_1" targetRef="Gateway_VIP" />
            <bpmn:sequenceFlow id="Flow_HighValue" name="Yes" sourceRef="Gateway_VIP" targetRef="Task_ManualReview">
              <bpmn:conditionExpression xsi:type="bpmn:tFormalExpression">amount &gt;= 1000</bpmn:conditionExpression>
            </bpmn:sequenceFlow>
            <bpmn:sequenceFlow id="Flow_Standard" name="No" sourceRef="Gateway_VIP" targetRef="Gateway_Merge">
              <bpmn:conditionExpression xsi:type="bpmn:tFormalExpression">amount &lt; 1000</bpmn:conditionExpression>
            </bpmn:sequenceFlow>
            <bpmn:sequenceFlow id="Flow_2" sourceRef="Task_ManualReview" targetRef="Gateway_Merge" />
            <bpmn:sequenceFlow id="Flow_3" sourceRef="Gateway_Merge" targetRef="Task_Invoice" />
            <bpmn:sequenceFlow id="Flow_End" sourceRef="Task_Invoice" targetRef="EndEvent_1" />
          </bpmn:process>
          <bpmn:message id="Message_Demo" name="demo_order_placed" />
          <bpmndi:BPMNDiagram id="BPMNDiagram_1">
            <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
              <bpmndi:BPMNShape id="_BPMNShape_StartEvent_2" bpmnElement="StartEvent_1">
                <dc:Bounds x="152" y="102" width="36" height="36" />
              </bpmndi:BPMNShape>
              <bpmndi:BPMNShape id="Gateway_VIP_di" bpmnElement="Gateway_VIP" isMarkerVisible="true">
                <dc:Bounds x="245" y="95" width="50" height="50" />
              </bpmndi:BPMNShape>
              <bpmndi:BPMNShape id="Task_ManualReview_di" bpmnElement="Task_ManualReview">
                <dc:Bounds x="350" y="80" width="100" height="80" />
              </bpmndi:BPMNShape>
              <bpmndi:BPMNShape id="Gateway_Merge_di" bpmnElement="Gateway_Merge" isMarkerVisible="true">
                <dc:Bounds x="505" y="95" width="50" height="50" />
              </bpmndi:BPMNShape>
              <bpmndi:BPMNShape id="Task_Invoice_di" bpmnElement="Task_Invoice">
                <dc:Bounds x="610" y="80" width="100" height="80" />
              </bpmndi:BPMNShape>
              <bpmndi:BPMNShape id="EndEvent_1_di" bpmnElement="EndEvent_1">
                <dc:Bounds x="762" y="102" width="36" height="36" />
              </bpmndi:BPMNShape>
              <bpmndi:BPMNEdge id="Flow_1_di" bpmnElement="Flow_1">
                <di:waypoint x="188" y="120" />
                <di:waypoint x="245" y="120" />
              </bpmndi:BPMNEdge>
              <bpmndi:BPMNEdge id="Flow_HighValue_di" bpmnElement="Flow_HighValue">
                <di:waypoint x="295" y="120" />
                <di:waypoint x="350" y="120" />
              </bpmndi:BPMNEdge>
              <bpmndi:BPMNEdge id="Flow_Standard_di" bpmnElement="Flow_Standard">
                <di:waypoint x="270" y="145" />
                <di:waypoint x="270" y="230" />
                <di:waypoint x="530" y="230" />
                <di:waypoint x="530" y="145" />
              </bpmndi:BPMNEdge>
              <bpmndi:BPMNEdge id="Flow_2_di" bpmnElement="Flow_2">
                <di:waypoint x="450" y="120" />
                <di:waypoint x="505" y="120" />
              </bpmndi:BPMNEdge>
              <bpmndi:BPMNEdge id="Flow_3_di" bpmnElement="Flow_3">
                <di:waypoint x="555" y="120" />
                <di:waypoint x="610" y="120" />
              </bpmndi:BPMNEdge>
              <bpmndi:BPMNEdge id="Flow_End_di" bpmnElement="Flow_End">
                <di:waypoint x="710" y="120" />
                <di:waypoint x="762" y="120" />
              </bpmndi:BPMNEdge>
            </bpmndi:BPMNPlane>
          </bpmndi:BPMNDiagram>
        </bpmn:definitions>';
    }
}