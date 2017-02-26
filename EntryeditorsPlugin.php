<?php

namespace Craft;

class EntryeditorsPlugin extends BasePlugin
{
    public function getName()
    {
        return Craft::t('Entry Editors');
    }

    public function getVersion()
    {
        return '1.0';
    }

    public function getDeveloper()
    {
        return 'Yuri Salimovskiy';
    }

    public function getDeveloperUrl()
    {
        return 'http://www.intoeetive.com';
    }

    public function hasCpSection()
    {
        return false;
    }
    
    protected function defineSettings()
	{
		return array(
			'field' => AttributeType::Mixed
		);
	}
    
    public function getSettingsHtml()
	{
		$options = [];
        foreach (craft()->fields->getAllFields('id') as $id=>$field)
		{
            if ($field->type=='Users')
            {
                $options[$id] = $field->name;
            }
		}

		$input = craft()->templates->render('_includes/forms/select', array(
			'id'         => 'field',
			'name'       => 'field',
			'options'    => $options,
			'value'      => $this->getSettings()['field']
		));
        
        return craft()->templates->render('_includes/forms/field', array(
            'label'      => Craft::t('Select Editors Field'),
            'instructions'=> Craft::t('Field that lists everyone that can edit entry (besides author and admins)'),
			'input'    => $input
		));
	}
    
    public function init()
    {
        craft()->on('entries.onBeforeSaveEntry', function(Event $event) 
        {
            $settings = $this->getSettings();
            //is setting set?
            if (!isset($settings->field) || empty($settings->field))
            {
                return true;
            }
            
            //skip check for new entries
            if ($event->params['isNewEntry']===true)
            {
                return true;
            }
            
            $user = craft()->userSession->user;
            //skip check for admin
            if (isset($user->admin) && $user->admin==true )
            {
                return true;
            }
            
            $entry = $event->params['entry'];
            //skip check for author
            if ($user->id == $entry->author->id)
            {
                return true;
            }
            
            //does the field relate to entry users?
            $criteria = craft()->elements->getCriteria(ElementType::User, ['relatedTo'    => [
                'element'  => $entry,
                'fieldId'  => $settings->field
            ]]);
            $editors = $criteria->find();
            
            if (empty($editors))
            {
                return true;
            }
            
            //is logged in user in the list?
            foreach ($editors as $editor)
            {
                if ($user->id == $editor->id)
                {
                    return true;
                }
            }
            
            //none of check passed, deny
            $event->performAction = false;
            
            throw new Exception(Craft::t('You are not allowed to edit this entry'));

        });
    }
    
}
