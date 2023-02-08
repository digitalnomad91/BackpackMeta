<?php

namespace AbbyJanke\BackpackMeta\ModelTraits;

use DB;
use Illuminate\Database\Eloquent\Model;
use AbbyJanke\BackpackMeta\app\Http\Models\Meta as Options;
use AbbyJanke\BackpackMeta\app\Http\Models\Values;

trait Meta
{

  /**
   * Connect to the Meta values
   **/
    public function meta()
    {
        return $this->hasMany('AbbyJanke\BackpackMeta\app\Http\Models\Values', 'record_id', 'id');
    }

    /**
     * Get all META options for the model.
     * @return void
     **/
    public function getMetaOptions()
    {
        $className = get_class($this->newInstance());
        return Options::where('model', $className)->get();
    }

    /**
     * Get all META options for the model.
     * @return void
     **/
    public function singleMetaOption($key)
    {
        return Options::where('key', $key)->first();
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        $query = $this->newQueryWithoutScopes();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if ($this->exists) {
            $saved = $this->isDirty() ?
                      $this->performUpdate($query) : true;
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $saved = $this->performInsert($query);

            if (! $this->getConnectionName() &&
              $connection = $query->getConnection()) {
                $this->setConnection($connection->getName());
            }
        }

        $newAttributes = \Request::except(['_token', 'save_action', 'new_option', '_method']);
        foreach ($newAttributes as $key => $attribute) {
            if (!\Schema::hasColumn($this->getTable(), $key)) {
                $optionInfo = $this->singleMetaOption($key);
                if ($currentValue = $this->meta->where('meta_id', $optionInfo->id)->first()) {
                    if (empty($attribute)) {
                        $currentValue->delete();
                    } else {
                        $currentValue->value = $attribute;
                        $currentValue->save();
                    }
                } else {
                    $newValue = Values::create([
                    'record_id' => $this->id,
                    'meta_id' => $optionInfo->id,
                    'value' => $attribute,
                  ]);
                }
            }
        }


        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.
        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $metaOptions = $this->getMetaOptions();
        $arrayedOptionKeys = [];

        foreach ($metaOptions as $option) {
            $arrayedOptionKeys[$option->id] = $option->key;
        }

        if (in_array($key, $arrayedOptionKeys)) {
            $attribute = $this->meta->where('meta_id', array_search($key, $arrayedOptionKeys))->first();
            if ($attribute) {
                $attribute = $attribute->value;
            }
        } else {
            $attribute = $this->getAttribute($key);
        }

        return $attribute;
    }
}
