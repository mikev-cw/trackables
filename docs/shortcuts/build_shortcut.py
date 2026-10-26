"""Build the unsigned native iOS shortcut; sign using Apple's `shortcuts sign`."""

import plistlib
import sys
import uuid


actions = []


def action(identifier, **params):
    uid = str(uuid.uuid4()).upper()
    # These keys differ between actions even when the editor calls them Input.
    if identifier == 'count':
        params['Input'] = params['WFInput']
    elif identifier == 'setvalueforkey':
        params['WFDictionary'] = params.pop('WFInput')
    elif identifier == 'downloadurl':
        params['WFURL'] = text(params['WFURL']['Value'])
        if params.get('WFHTTPBodyType') == 'File':
            params['WFFormValues'] = dictionary()
    elif identifier == 'url':
        params['Show-WFURLActionURL'] = True
    elif identifier == 'format.date':
        params['WFDate'] = text(params['WFDate']['Value'])

    output_name = {
        'gettext': 'Text', 'url': 'URL', 'downloadurl': 'Contents of URL',
        'detect.dictionary': 'Dictionary', 'dictionary': 'Dictionary',
        'getvalueforkey': 'Dictionary Value', 'setvalueforkey': 'Dictionary',
        'count': 'Count', 'getitemfromlist': 'Item from List',
        'choosefromlist': 'Chosen Item', 'ask': 'Provided Input',
        'format.date': 'Formatted Date', 'list': 'List',
    }.get(identifier, 'Result')
    actions.append({
        'WFWorkflowActionIdentifier': 'is.workflow.actions.' + identifier,
        'WFWorkflowActionParameters': dict(params, UUID=uid),
    })
    return {'Type': 'ActionOutput', 'OutputUUID': uid, 'OutputName': output_name}


def variable(name):
    return {'Type': 'Variable', 'VariableName': name}


def attachment(value):
    return {'Value': value, 'WFSerializationType': 'WFTextTokenAttachment'}


def text(*parts):
    string, ranges = '', {}
    for part in parts:
        if isinstance(part, dict):
            offset = len(string.encode('utf-16-le')) // 2
            ranges['{%d, 1}' % offset] = part
            string += '\ufffc'
        else:
            string += str(part)
    return {'Value': {'string': string, 'attachmentsByRange': ranges},
            'WFSerializationType': 'WFTextTokenString'}


def literal(value):
    return action('gettext', WFTextActionText=text(value))


def store(name, value):
    action('setvariable', WFVariableName=name, WFInput=attachment(value))
    return variable(name)


def get(source, key):
    return action('getvalueforkey', WFInput=attachment(source),
                  WFGetDictionaryValueType='Value', WFDictionaryKey=text(key))


def dictionary(items=None):
    fields = [{'WFItemType': 0, 'WFKey': text(k), 'WFValue': text(v)}
              for k, v in (items or {}).items()]
    return {'Value': {'WFDictionaryFieldValueItems': fields},
            'WFSerializationType': 'WFDictionaryFieldValue'}


def empty(name):
    return store(name, action('dictionary', WFItems=dictionary()))


def set_key(name, key, value):
    return store(name, action('setvalueforkey', WFInput=attachment(variable(name)),
                             WFDictionaryKey=text(key), WFDictionaryValue=text(value)))


def begin_if(value, comparison=None, condition=4):
    group = str(uuid.uuid4()).upper()
    # If uses a parameter-state wrapper, unlike normal action inputs.
    # Text coercion makes comparisons from dictionary/number outputs editable.
    if comparison is not None:
        value = action('gettext', WFTextActionText=text(value))
        comparison = str(comparison)
    params = dict(WFInput={'Type': 'Variable', 'Variable': attachment(value)}, WFCondition=condition,
                  WFControlFlowMode=0, GroupingIdentifier=group)
    if comparison is not None:
        params['WFConditionalActionString'] = comparison
        comment('- Continue in this branch when the preceding text equals '+comparison+'.')
    else:
        comment('- Check whether the API returned a trackable before reading its schema.')
    action('conditional', **params)
    return group


def begin_numeric_if(value, comparison, condition):
    """Compare numbers/JSON booleans without passing through localized text."""
    group = str(uuid.uuid4()).upper()
    number = dict(value)
    number['Aggrandizements'] = [{
        'Type': 'WFCoercionVariableAggrandizement',
        'CoercionItemClass': 'WFNumberContentItem',
    }]
    relation = 'greater than' if condition == 2 else 'less than'
    comment(f'- Continue when the preceding numeric value is {relation} {comparison}.')
    action('conditional',
           WFInput={'Type': 'Variable', 'Variable': attachment(number)},
           WFCondition=condition, WFNumberValue=str(comparison),
           WFControlFlowMode=0, GroupingIdentifier=group)
    return group


def otherwise(group):
    action('conditional', WFControlFlowMode=1, GroupingIdentifier=group)


def end_if(group):
    action('conditional', WFControlFlowMode=2, GroupingIdentifier=group)


def stop(message):
    action('showresult', Text=message if isinstance(message, dict) else text(message))
    action('exit')


def choose(items, prompt):
    return action('choosefromlist', WFInput=attachment(items),
                  WFChooseFromListActionPrompt=text(prompt),
                  WFChooseFromListActionSelectMultiple=False)


def ask(prompt, kind='Text'):
    return action('ask', WFAskActionPrompt=text(prompt), WFInputType=kind,
                  WFAllowsMultilineText=kind == 'Text')


def comment(message):
    action('comment', WFCommentActionText=message)


comment('Trackables: New Record\nEach run downloads the latest schema and enum values, asks for field values, and submits one record.')
comment('Edit the next three Text actions once. The URL must end in /api/trackable, without a trailing slash. Use your Sanctum token without the Bearer prefix.\nCancel any prompt to stop without submitting.')
api_key = store('API Key', literal('PASTE_API_TOKEN_HERE'))
base_url = store('Trackables URL', literal('https://YOUR_DOMAIN/api/trackable'))
alias = store('Trackable Alias', literal('YOUR_TRACKABLE_ALIAS'))

headers = dictionary()
headers['Value']['WFDictionaryFieldValueItems'].append(
    {'WFItemType': 0, 'WFKey': text('Authorization'), 'WFValue': text('Bearer ', api_key)})
headers['Value']['WFDictionaryFieldValueItems'].append(
    {'WFItemType': 0, 'WFKey': text('Accept'), 'WFValue': text('application/json')})
headers['Value']['WFDictionaryFieldValueItems'].append(
    {'WFItemType': 0, 'WFKey': text('Content-Type'), 'WFValue': text('application/json')})

comment('Fetch the current trackable, including the schema and all enum values.')
url = store('Trackable URL', action('url', WFURLActionURL=text(base_url, '/', alias)))
response = action('downloadurl', WFURL=attachment(url), WFHTTPMethod='GET',
                  Advanced=True, ShowHeaders=True, WFHTTPHeaders=headers)
parsed = store('API Response', action('detect.dictionary', WFInput=attachment(response)))
trackable = get(parsed, 'data')
check = begin_if(trackable, condition=101)  # does not have any value
stop(text('Could not load trackable. API response:\n', parsed))
end_if(check)
schema = store('Schema', get(trackable, 'schema'))
count = action('count', WFInput=attachment(schema), WFCountType='Items')
check = begin_numeric_if(count, 1, condition=0)
stop('This trackable has no fields.')
end_if(check)
empty('Record')

comment('- Repeat once for every schema field.\n- Optional fields can be skipped.\n- Enum choices include only active values; the submitted value is the selected UID.')
loop = str(uuid.uuid4()).upper()
action('repeat.count', WFRepeatCount=attachment(count), WFControlFlowMode=0,
       GroupingIdentifier=loop)
field = store('Field', action('getitemfromlist', WFInput=attachment(schema),
                            WFItemSpecifier='Item At Index',
                            WFItemIndex=attachment(variable('Repeat Index'))))
name = store('Field Name', get(field, 'name'))
field_uid = store('Field UID', get(field, 'uid'))
field_type = store('Field Type', get(field, 'field_type'))
required = get(field, 'validation_config.required')
store('Fill Field', literal('Yes'))
optional = begin_numeric_if(required, 1, condition=0)
options = action('list', WFItems=['Fill in', 'Skip'])
selection = choose(options, name)
skip = begin_if(selection, 'Skip')
store('Fill Field', literal('No'))
end_if(skip)
end_if(optional)
fill = begin_if(variable('Fill Field'), 'Yes')

enum_case = begin_if(field_type, 'enum')
empty('Enum Choices')
enum_values = get(get(field, 'enum'), 'values')
enum_count = action('count', WFInput=attachment(enum_values), WFCountType='Items')
inner_loop = str(uuid.uuid4()).upper()
comment('- Inspect each enum option.\n- Add active options to the choices dictionary.')
action('repeat.count', WFRepeatCount=attachment(enum_count), WFControlFlowMode=0,
       GroupingIdentifier=inner_loop)
enum_item = store('Enum Item', action('getitemfromlist', WFInput=attachment(enum_values),
                                    WFItemSpecifier='Item At Index',
                                    WFItemIndex=attachment(variable('Repeat Index 2'))))
active = get(enum_item, 'is_active')
is_active = begin_numeric_if(active, 0, condition=2)
label = get(enum_item, 'label')
key = get(enum_item, 'key')
uid = get(enum_item, 'uid')
display = action('gettext', WFTextActionText=text(label, ' [', key, ']'))
set_key('Enum Choices', display, uid)
end_if(is_active)
action('repeat.count', WFControlFlowMode=2, GroupingIdentifier=inner_loop)
keys = action('getvalueforkey', WFInput=attachment(variable('Enum Choices')),
              WFGetDictionaryValueType='All Keys')
num_options = action('count', WFInput=attachment(keys), WFCountType='Items')
no_options = begin_numeric_if(num_options, 1, condition=0)
stop(text('No active enum choices are available for ', name,
          '. Enum values received: ', enum_count,
          '. Update the list, or skip this field if optional.'))
end_if(no_options)
chosen = choose(keys, name)
store('Answer', get(variable('Enum Choices'), chosen))
otherwise(enum_case)

bool_case = begin_if(field_type, 'bool')
options = action('list', WFItems=['Yes', 'No'])
chosen = choose(options, name)
yes = begin_if(chosen, 'Yes')
store('Answer', literal('1'))
otherwise(yes)
store('Answer', literal('0'))
end_if(yes)
otherwise(bool_case)

date_case = begin_if(field_type, 'date')
provided = ask(name, 'Date')
formatted = action('format.date', WFDate=attachment(provided), WFDateFormatStyle='Custom',
                   WFDateFormat='Custom', WFDateFormatString='yyyy-MM-dd', WFTimeFormatStyle='None')
store('Answer', formatted)
otherwise(date_case)
datetime_case = begin_if(field_type, 'datetime')
provided = action('ask', WFAskActionPrompt=text(name), WFInputType='Date',
                  WFAskActionDateGranularity='Date and Time')
formatted = action('format.date', WFDate=attachment(provided), WFDateFormatStyle='Custom',
                   WFDateFormat='Custom', WFDateFormatString="yyyy-MM-dd'T'HH:mm:ssXXX", WFTimeFormatStyle='None')
store('Answer', formatted)
otherwise(datetime_case)
int_case = begin_if(field_type, 'int')
provided = action('ask', WFAskActionPrompt=text(name, ' (whole number)'), WFInputType='Number')
store('Answer', provided)
otherwise(int_case)
float_case = begin_if(field_type, 'float')
store('Answer', ask(name, 'Number'))
otherwise(float_case)
store('Answer', ask(name))
end_if(float_case)
end_if(int_case)
end_if(datetime_case)
end_if(date_case)
end_if(bool_case)
end_if(enum_case)

set_key('Record', field_uid, variable('Answer'))
end_if(fill)
action('repeat.count', WFControlFlowMode=2, GroupingIdentifier=loop)

comment('Serialize the assembled dictionary as JSON, then send it as the HTTP body. The server assigns the current record date. API errors are shown as returned.')
payload = action('gettext', WFTextActionText=text(variable('Record')))
post_url = action('url', WFURLActionURL=text(url, '/record'))
result = action('downloadurl', WFURL=attachment(post_url), WFHTTPMethod='POST',
                Advanced=True, ShowHeaders=True, WFHTTPHeaders=headers,
                WFHTTPBodyType='File', WFRequestVariable=attachment(payload))
action('showresult', Text=text(result))

workflow = {
    'WFWorkflowName': 'Trackables - New Record',
    'WFWorkflowClientVersion': '3030.0.4',
    'WFWorkflowMinimumClientVersion': 900,
    'WFWorkflowMinimumClientVersionString': '900',
    'WFWorkflowIcon': {'WFWorkflowIconStartColor': 4282601983,
                       'WFWorkflowIconGlyphNumber': 61440},
    'WFWorkflowTypes': [],
    'WFWorkflowInputContentItemClasses': [],
    'WFWorkflowImportQuestions': [],
    'WFWorkflowActions': actions,
}

if __name__ == '__main__':
    with open(sys.argv[1], 'wb') as output:
        plistlib.dump(workflow, output, fmt=plistlib.FMT_BINARY)
    print(f'Built {len(actions)} native actions: {sys.argv[1]}')
