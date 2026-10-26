"""Regression checks for native parameter serialization and schema-driven flow.

Run with: python3 docs/shortcuts/test_shortcut.py
These checks simulate data flow; they do not replace a test in Apple's editor.
"""

import json
import re

from build_shortcut import workflow

actions = workflow['WFWorkflowActions']

# Check every reference and token range, and each control-flow bracket.
seen, stack = set(), []
def validate(value):
    if isinstance(value, list):
        for item in value:
            validate(item)
    elif isinstance(value, dict):
        if value.get('Type') == 'ActionOutput':
            assert value['OutputUUID'] in seen, value
        if value.get('WFSerializationType') == 'WFTextTokenString':
            body = value['Value']
            encoded = body['string'].encode('utf-16-le')
            assert body['string'].count('\ufffc') == len(body['attachmentsByRange'])
            for key in body['attachmentsByRange']:
                offset = int(re.search(r'\d+', key)[0])
                assert encoded[offset*2:offset*2+2] == '\ufffc'.encode('utf-16-le')
        for item in value.values():
            validate(item)

for a in actions:
    p = a['WFWorkflowActionParameters']
    name = a['WFWorkflowActionIdentifier'].split('actions.')[1]
    validate(p)
    if name == 'conditional' and p['WFControlFlowMode'] == 0:
        assert p['WFInput']['Type'] == 'Variable'
        assert p['WFInput']['Variable']['WFSerializationType'] == 'WFTextTokenAttachment'
        if p['WFCondition'] == 4:
            assert isinstance(p['WFConditionalActionString'], str)
            assert 'WFNumberValue' not in p
        elif p['WFCondition'] in (0, 2):
            assert isinstance(p['WFNumberValue'], str)
            assert 'WFConditionalActionString' not in p
            assert p['WFInput']['Variable']['Value']['Aggrandizements'][0]['CoercionItemClass'] == 'WFNumberContentItem'
    elif name == 'downloadurl':
        assert p['WFURL']['WFSerializationType'] == 'WFTextTokenString'
        assert p['WFURL']['Value']['attachmentsByRange']
    elif name == 'setvalueforkey':
        assert p['WFDictionary']['WFSerializationType'] == 'WFTextTokenAttachment'
        assert 'WFInput' not in p
    elif name == 'count':
        assert p['Input'] == p['WFInput']
    elif name == 'format.date':
        assert p['WFDate']['WFSerializationType'] == 'WFTextTokenString'
        assert p['WFDateFormat'] == 'Custom'
        assert p['WFDateFormatString']
    if 'WFControlFlowMode' in p:
        mode, group = p['WFControlFlowMode'], p['GroupingIdentifier']
        if mode == 0:
            stack.append(group)
        else:
            assert stack[-1] == group
            if mode == 2:
                stack.pop()
    seen.add(p['UUID'])
assert not stack


def simulate(schema, choices):
    outputs, variables, requests, shown = {}, {}, [], []
    answers = iter(choices)
    repeat_stack = []

    def render(x):
        if isinstance(x, dict):
            if x.get('Type') == 'Variable' and 'Variable' in x:
                return render(x['Variable'])
            kind = x.get('WFSerializationType')
            if kind == 'WFTextTokenAttachment':
                return render(x['Value'])
            if kind == 'WFTextTokenString':
                v = x['Value']
                s = v['string']
                for key, ref in sorted(v['attachmentsByRange'].items(), reverse=True,
                                       key=lambda pair: int(re.search(r'\d+', pair[0])[0])):
                    pos = int(re.search(r'\d+', key)[0])
                    out = render(ref)
                    if isinstance(out, (dict, list)):
                        out = json.dumps(out)
                    if out is None:
                        out = ''
                    s = s[:pos] + str(out) + s[pos+1:]
                return s
            if kind == 'WFDictionaryFieldValue':
                return {render(i['WFKey']): render(i['WFValue'])
                        for i in x['Value']['WFDictionaryFieldValueItems']}
            if x.get('Type') == 'Variable':
                return variables[x['VariableName']]
            if x.get('Type') == 'ActionOutput':
                return outputs[x['OutputUUID']]
        return x

    def run(start, end):
        i = start
        while i < end:
            a = actions[i]
            name = a['WFWorkflowActionIdentifier'].split('actions.')[1]
            p = a['WFWorkflowActionParameters']
            out = None
            if p.get('WFControlFlowMode') == 0:
                group = p['GroupingIdentifier']
                finish = next(j for j in range(i+1, end) if
                              actions[j]['WFWorkflowActionParameters'].get('GroupingIdentifier') == group
                              and actions[j]['WFWorkflowActionParameters'].get('WFControlFlowMode') == 2)
                middle = next((j for j in range(i+1, finish) if
                               actions[j]['WFWorkflowActionParameters'].get('GroupingIdentifier') == group
                               and actions[j]['WFWorkflowActionParameters'].get('WFControlFlowMode') == 1), finish)
                if name == 'conditional':
                    v = render(p['WFInput'])
                    if p['WFCondition'] == 101:
                        matches = not v
                    elif 'WFNumberValue' in p:
                        number = float(p['WFNumberValue'])
                        matches = float(v) < number if p['WFCondition'] == 0 else float(v) > number
                    else:
                        matches = str(v) == render(p['WFConditionalActionString'])
                    if matches:
                        run(i+1, middle)
                    elif middle < finish:
                        run(middle+1, finish)
                elif name == 'repeat.count':
                    repeat_stack.append(None)
                    index_name = 'Repeat Index' + (' '+str(len(repeat_stack)) if len(repeat_stack) > 1 else '')
                    for n in range(1, int(render(p['WFRepeatCount']))+1):
                        variables[index_name] = n
                        run(i+1, finish)
                    repeat_stack.pop()
                elif name == 'repeat.each':
                    for item in render(p['WFInput']):
                        variables['Repeat Item'] = item
                        run(i+1, finish)
                i = finish + 1
                continue
            if name == 'gettext':
                out = render(p['WFTextActionText'])
            elif name == 'setvariable':
                out = render(p['WFInput'])
                variables[p['WFVariableName']] = out
            elif name == 'url':
                out = render(p['WFURLActionURL'])
            elif name == 'downloadurl':
                if p['WFHTTPMethod'] == 'GET':
                    out = {'data': {'schema': schema}}
                else:
                    requests.append(json.loads(render(p['WFRequestVariable'])))
                    out = {'success': True}
            elif name == 'detect.dictionary':
                out = render(p['WFInput'])
            elif name == 'dictionary':
                out = render(p['WFItems'])
            elif name == 'getvalueforkey':
                out = render(p['WFInput'])
                if p['WFGetDictionaryValueType'] == 'All Keys':
                    out = list(out)
                else:
                    for key in render(p['WFDictionaryKey']).split('.'):
                        out = out.get(key) if out else None
            elif name == 'count':
                out = len(render(p['Input']))
            elif name == 'getitemfromlist':
                out = render(p['WFInput'])[int(render(p['WFItemIndex']))-1]
            elif name == 'list':
                out = p['WFItems']
            elif name == 'choosefromlist':
                out = next(answers)
                assert out in render(p['WFInput']), (out, render(p['WFInput']))
            elif name == 'ask':
                out = next(answers)
            elif name == 'format.date':
                out = render(p['WFDate'])
            elif name == 'setvalueforkey':
                out = dict(render(p['WFDictionary']))
                out[render(p['WFDictionaryKey'])] = render(p['WFDictionaryValue'])
            elif name == 'showresult':
                shown.append(render(p['Text']))
            elif name == 'exit':
                raise RuntimeError('Stopped: ' + str(shown[-1]))
            elif name != 'comment':
                raise AssertionError(name)
            outputs[p['UUID']] = out
            i += 1
    run(0, len(actions))
    return requests


def field(uid, kind, required=True, **extra):
    return dict(uid=uid, name=uid, field_type=kind,
                validation_config={'required': required}, **extra)

schema = [
    field('status', 'enum', enum={'values': [
        {'uid': 'retired-uid', 'key': 'old', 'label': 'Old', 'is_active': False},
        {'uid': 'pending-uid', 'key': 'pending', 'label': 'Pending', 'is_active': True}]}),
    field('memo', 'string'), field('qty', 'int'), field('cost', 'float'),
    field('paid', 'bool'), field('day', 'date'), field('time', 'datetime'),
    field('json', 'json'), field('optional', 'string', required=False),
]
requests = simulate(schema, ['Pending [pending]', 'Text "quoted"\nNext line', 5, 1.25,
                             'No', '2026-10-16', '2026-10-16T10:00:00+02:00',
                             '{"key":1}', 'Skip'])
assert requests == [{'status': 'pending-uid', 'memo': 'Text "quoted"\nNext line',
                     'qty': '5', 'cost': '1.25', 'paid': '0', 'day': '2026-10-16',
                     'time': '2026-10-16T10:00:00+02:00', 'json': '{"key":1}'}], requests
schema[0]['enum']['values'][1]['label'] = 'Updated'
assert simulate(schema[:1], ['Updated [pending]']) == [{'status': 'pending-uid'}]
schema[0]['enum']['values'].extend([
    {'uid': 'second-uid', 'key': 'second', 'label': 'Second', 'is_active': True},
    {'uid': 'third-uid', 'key': 'third', 'label': 'Third', 'is_active': True},
])
second_enum = field('second_status', 'enum', enum={'values': [
    {'uid': 'another-uid', 'key': 'another', 'label': 'Another', 'is_active': 1},
    {'uid': 'inactive-uid', 'key': 'inactive', 'label': 'Inactive', 'is_active': 0},
]})
assert simulate([schema[0], second_enum], ['Third [third]', 'Another [another]']) == [
    {'status': 'third-uid', 'second_status': 'another-uid'}]
assert simulate([field('optional', 'string', required=False)], ['Fill in', 'value']) == [{'optional': 'value'}]
try:
    simulate([field('status', 'enum', enum={'values': []})], [])
except RuntimeError as error:
    assert 'No active enum choices' in str(error)
else:
    raise AssertionError('Empty enum must stop submission')
print('PASS: native parameter shapes, references, control flow, JSON boolean and numeric enum flags, three active choices, multiple enums, refreshed labels, typed fields, JSON escaping, optional fields, empty-enum stop.')
