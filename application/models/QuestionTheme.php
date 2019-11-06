<?php

use LimeSurvey\Helpers\questionHelper;

/**
 * This is the model class for table "{{question_themes}}".
 *
 * The followings are the available columns in table '{{question_themes}}':
 *
 * @property integer $id
 * @property string  $name
 * @property string  $visible
 * @property string  $xml_path
 * @property string  $image_path
 * @property string  $title
 * @property string  $creation_date
 * @property string  $author
 * @property string  $author_email
 * @property string  $author_url
 * @property string  $copyright
 * @property string  $license
 * @property string  $version
 * @property string  $api_version
 * @property string  $description
 * @property string  $last_update
 * @property integer $owner_id
 * @property string  $theme_type
 * @property string  $question_type
 * @property integer $core_theme
 * @property string  $extends
 * @property string  $group
 * @property string  $settings
 */
class QuestionTheme extends LSActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{question_themes}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            [
                'name',
                'unique',
                'caseSensitive' => false,
                'criteria'      => [
                    'condition' => '`extends`=:extends',
                    'params'    => [
                        ':extends' => $this->extends
                    ]
                ],
            ],
            array('name, title, api_version, question_type', 'required'),
            array('owner_id, core_theme', 'numerical', 'integerOnly' => true),
            array('name, author, theme_type, question_type, extends, group', 'length', 'max' => 150),
            array('visible', 'length', 'max' => 1),
            array('xml_path, image_path, author_email, author_url', 'length', 'max' => 255),
            array('title', 'length', 'max' => 100),
            array('version, api_version', 'length', 'max' => 45),
            array('creation_date, copyright, license, description, last_update, settings', 'safe'),
            // The following rule is used by search().
            array('id, name, visible, xml_path, image_path, title, creation_date, author, author_email, author_url, copyright, license, version, api_version, description, last_update, owner_id, theme_type, question_type, core_theme, extends, group, settings', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array();
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id'            => 'ID',
            'name'          => 'Name',
            'visible'       => 'Visible',
            'xml_path'      => 'Xml Path',
            'image_path'    => 'Image Path',
            'title'         => 'Title',
            'creation_date' => 'Creation Date',
            'author'        => 'Author',
            'author_email'  => 'Author Email',
            'author_url'    => 'Author Url',
            'copyright'     => 'Copyright',
            'license'       => 'License',
            'version'       => 'Version',
            'api_version'   => 'Api Version',
            'description'   => 'Description',
            'last_update'   => 'Last Update',
            'owner_id'      => 'Owner',
            'theme_type'    => 'Theme Type',
            'question_type' => 'Question Type',
            'core_theme'    => 'Core Theme',
            'extends'       => 'Extends',
            'group'         => 'Group',
            'settings'      => 'Settings',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        $pageSizeTemplateView = App()->user->getState('pageSizeTemplateView', App()->params['defaultPageSize']);

        $criteria = new CDbCriteria;
        $criteria->compare('id', $this->id);
        $criteria->compare('name', $this->name, true);
        $criteria->compare('visible', $this->visible, true);
        $criteria->compare('xml_path', $this->xml_path, true);
        $criteria->compare('image_path', $this->image_path, true);
        $criteria->compare('title', $this->title, true);
        $criteria->compare('creation_date', $this->creation_date, true);
        $criteria->compare('author', $this->author, true);
        $criteria->compare('author_email', $this->author_email, true);
        $criteria->compare('author_url', $this->author_url, true);
        $criteria->compare('copyright', $this->copyright, true);
        $criteria->compare('license', $this->license, true);
        $criteria->compare('version', $this->version, true);
        $criteria->compare('api_version', $this->api_version, true);
        $criteria->compare('description', $this->description, true);
        $criteria->compare('last_update', $this->last_update, true);
        $criteria->compare('owner_id', $this->owner_id);
        $criteria->compare('theme_type', $this->theme_type, true);
        $criteria->compare('question_type', $this->question_type, true);
        $criteria->compare('core_theme', $this->core_theme);
        $criteria->compare('extends', $this->extends, true);
        $criteria->compare('group', $this->group, true);
        $criteria->compare('settings', $this->settings, true);
        return new CActiveDataProvider($this, array(
            'criteria'   => $criteria,
            'pagination' => array(
                'pageSize' => $pageSizeTemplateView,
            ),
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     *
     * @param string $className active record class name.
     *
     * @return Template the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Returns this table's primary key
     *
     * @access public
     * @return string
     */
    public function primaryKey()
    {
        return 'id';
    }

    /**
     * Import all Questiontypes and Themes to the {{questions_themes}} table
     *
     * @param bool $useTransaction
     *
     * @throws CException
     */
    public function loadAllQuestionXMLConfigurationsIntoDatabase($useTransaction = true)
    {
        $missingQuestionThemeAttributes = [];
        $questionThemeDirectories = $this->getQuestionThemeDirectories();

        // @see: http://phpsecurity.readthedocs.io/en/latest/Injection-Attacks.html#xml-external-entity-injection
        $bOldEntityLoaderState = libxml_disable_entity_loader(true);
        // process XML Question Files
        if (isset($questionThemeDirectories)) {
            try {
                if ($useTransaction) {
                    $transaction = App()->db->beginTransaction();
                }
                $questionsMetaData = self::getAllQuestionMetaData();
                foreach ($questionsMetaData as $questionMetaData) {
                    // test xml for required metaData
                    $requiredMetaDataArray = ['name', 'title', 'creationDate', 'author', 'authorEmail', 'authorUrl', 'copyright', 'copyright', 'license', 'version', 'apiVersion', 'description', 'question_type', 'group', 'subquestions', 'answerscales', 'hasdefaultvalues', 'assessable', 'class'];
                    foreach ($requiredMetaDataArray as $requiredMetaData) {
                        if (!array_key_exists($requiredMetaData, $questionMetaData)) {
                            $missingQuestionThemeAttributes[$questionMetaData['xml_path']][] = $requiredMetaData;
                        }
                    }
                    $questionTheme = QuestionTheme::model()->find('name=:name AND extends=:extends', [':name' => $questionMetaData['name'], ':extends' => $questionMetaData['extends']]);
                    if ($questionTheme == null) {
                        $questionTheme = new QuestionTheme();
                    }
                    $metaDataArray = $this->getMetaDataArray($questionMetaData);
                    $questionTheme->setAttributes($metaDataArray, false);
                    $questionTheme->save();
                }
                if ($useTransaction) {
                    $transaction->commit();
                }
            } catch (Exception $e) {
                //TODO: flashmessage for users
                echo $e->getMessage();
                var_dump($e->getTrace());
                var_dump($missingQuestionThemeAttributes);
                if ($useTransaction) {
                    $transaction->rollback();
                }
            }
        }

        // Put back entity loader to its original state, to avoid contagion to other applications on the server
        libxml_disable_entity_loader($bOldEntityLoaderState);
    }

    // TODO: Enable when Configuration Model is ready
    public function getButtons()
    {
        // don't show any buttons if user doesn't have update permission
        if (!Permission::model()->hasGlobalPermission('templates', 'update')) {
            return '';
        }
        $sVisible = $this->visible == 'Y' ? true : false;
        $aButtons = [
            'visibility_button' => [
                'url' => $sToggleVisibilityUrl = Yii::app()->getController()->createUrl('admin/questionthemes/sa/togglevisibility', ['id' => $this->id]),
                'visible' => $sVisible
            ]
        ];
        $sButtons = Yii::app()->getController()->renderPartial('/admin/themeoptions/partials/question_themes/theme_buttons', ['id' => $this->id, 'buttons' => $aButtons], true);
        return $sButtons;
    }

    /**
     * Install Button for the available questions
     */
    public function getManifestButtons()
    {
        $sLoadLink = CHtml::form(array("/admin/themeoptions/sa/importmanifest/"), 'post', array('id' => 'forminstallquestiontheme', 'name' => 'forminstallquestiontheme')) .
            "<input type='hidden' name='templatefolder' value='" . $this->xml_path . "'>
            <input type='hidden' name='theme' value='questiontheme'>
            <button id='template_options_link_" . $this->name . "'class='btn btn-default btn-block'>
            <span class='fa fa-download text-warning'></span>
            " . gT('Install') . "
            </button>
            </form>";

        return $sLoadLink;
    }

    /**
     * Import config manifest to database.
     *
     * @param string $pathToXML
     *
     * @return bool|string
     * @throws Exception
     */
    public function importManifest($pathToXML)
    {
        if (empty($pathToXML)) {
            throw new InvalidArgumentException('$templateFolder cannot be empty');
        }
        /** @var array */
        $aQuestionMetaData = $this->getQuestionMetaData($pathToXML);

        /** @var array<string, mixed> */
        $aMetaDataArray = $this->getMetaDataArray($aQuestionMetaData);
        $this->setAttributes($aMetaDataArray, false);
        if ($this->save()) {
            return $aQuestionMetaData['title'];
        } else {
            // todo detailed error handling
            return null;
        }
    }

    /**
     * Returns all Questions that can be installed
     *
     * @return QuestionTheme[]
     * @throws Exception
     */
    public function getAvailableQuestions()
    {
        $questionThemes = $installedQuestions = $availableQuestions = $questionKeys = [];
        $questionsMetaData = $this->getAllQuestionMetaData();
        $questionsInDB = $this->findAll();

        if (!empty($questionsInDB)) {
            foreach ($questionsInDB as $questionInDB) {
                if (array_key_exists($questionKey = $questionInDB->name . '_' . $questionInDB->question_type, $questionsMetaData)) {
                    unset($questionsMetaData[$questionKey]);
                }
            }
        }
        if (!empty($questionsMetaData)) {
            array_values($questionsMetaData);
            foreach ($questionsMetaData as $questionMetaData) {
                // TODO: replace by manifest
                $questionTheme = new QuestionTheme();

                $metaDataArray = $this->getMetaDataArray($questionMetaData);
                $questionTheme->setAttributes($metaDataArray, false);
                $questionThemes[] = $questionTheme;
            }
        }

        return $questionThemes;
    }

    /**
     * Returns an Array of all questionthemes and their metadata
     *
     * @return array
     * @throws Exception
     */
    public function getAllQuestionMetaData()
    {
        $questionsMetaData = [];
        $questionDirectoriesAndPaths = $this->getAllQuestionXMLPaths();
        if (isset($questionDirectoriesAndPaths) && !empty($questionDirectoriesAndPaths)) {
            foreach ($questionDirectoriesAndPaths as $directory => $questionConfigFilePaths) {
                foreach ($questionConfigFilePaths as $questionConfigFilePath) {
                    $questionMetaData = self::getQuestionMetaData($questionConfigFilePath);
                    $questionsMetaData[$questionMetaData['name'] . '_' . $questionMetaData['questionType']] = $questionMetaData;
                }
            }
        }
        return $questionsMetaData;
    }

    /**
     * Read all the MetaData for given Question XML definition
     *
     * @param $pathToXML
     *
     * @return array Question Meta Data
     * @throws Exception
     */
    public static function getQuestionMetaData($pathToXML)
    {
        $questionDirectories = self::getQuestionThemeDirectories();

        foreach ($questionDirectories as $key => $questionDirectory) {
            $questionDirectories[$key] = str_replace('\\', '/', $questionDirectory);
        }
        $pathToXML = str_replace('\\', '/', $pathToXML);

        $bOldEntityLoaderState = libxml_disable_entity_loader(true);
        $publicurl = App()->getConfig('publicurl');

        $sQuestionConfigFile = file_get_contents(App()->getConfig('rootdir') . DIRECTORY_SEPARATOR . $pathToXML . DIRECTORY_SEPARATOR . 'config.xml');  // @see: Now that entity loader is disabled, we can't use simplexml_load_file; so we must read the file with file_get_contents and convert it as a string
        $oQuestionConfig = simplexml_load_string($sQuestionConfigFile);

        // TODO: Copied from PluginManager - remake to extension manager.
        $extensionConfig = new ExtensionConfig($oQuestionConfig);
        if (!$extensionConfig->validate()) {
            throw new Exception(gT('Extension configuration file is not valid.'));
        }
        if (!$extensionConfig->isCompatible()) {
            throw new Exception(
                sprintf(
                    gT('Extension "%s" is not compatible with your LimeSurvey version.'),
                    $extensionConfig->getName()
                )
            );
        }

        // read all metadata from the provided $pathToXML
        $questionMetaData = json_decode(json_encode($oQuestionConfig->metadata), true);

        // get custom previewimage if defined
        if (!empty($oQuestionConfig->files->preview->filename)) {
            $previewFileName = json_decode(json_encode($oQuestionConfig->files->preview->filename), true)[0];
            $questionMetaData['image_path'] = $publicurl . $pathToXML . '/assets/' . $previewFileName;
        }

        $questionMetaData['xml_path'] = $pathToXML;

        // set settings as json
        $questionMetaData['settings'] = json_encode([
            'subquestions'     => $questionMetaData['subquestions'] ?? 0,
            'answerscales'     => $questionMetaData['answerscales'] ?? 0,
            'hasdefaultvalues' => $questionMetaData['hasdefaultvalues'] ?? 0,
            'assessable'       => $questionMetaData['assessable'] ?? 0,
            'class'            => $questionMetaData['class'] ?? '',
        ]);

        // override MetaData depending on directory
        if (substr($pathToXML, 0, strlen($questionDirectories['coreQuestion'])) === $questionDirectories['coreQuestion']) {
            $questionMetaData['coreTheme'] = 1;
            $questionMetaData['extends'] = '';
            $questionMetaData['image_path'] = App()->getConfig("imageurl") . '/screenshots/' . self::getQuestionThemeImageName($questionMetaData['questionType']);
        }
        if (substr($pathToXML, 0, strlen($questionDirectories['customCoreTheme'])) === $questionDirectories['customCoreTheme']) {
            $questionMetaData['coreTheme'] = 1;
            $questionMetaData['extends'] = $questionMetaData['questionType'];
        }
        if (substr($pathToXML, 0, strlen($questionDirectories['customUserTheme'])) === $questionDirectories['customUserTheme']) {
            $questionMetaData['coreTheme'] = 0;
            $questionMetaData['extends'] = $questionMetaData['questionType'];
        }

        // get Default Image if undefined
        if (empty($questionMetaData['image_path']) || !file_exists(App()->getConfig('rootdir') . $questionMetaData['image_path'])) {
            $questionMetaData['image_path'] = App()->getConfig("imageurl") . '/screenshots/' . self::getQuestionThemeImageName($questionMetaData['questionType']);
        }

        libxml_disable_entity_loader($bOldEntityLoaderState);
        return $questionMetaData;
    }

    /**
     * Find all XML paths for specified Question Root folders
     *
     * @param bool $core
     * @param bool $custom
     * @param bool $user
     *
     * @return array
     */
    public function getAllQuestionXMLPaths($core = true, $custom = true, $user = true)
    {
        $questionDirectories = $this->getQuestionThemeDirectories();
        $questionDirectoriesAndPaths = [];
        if ($core) {
            $coreQuestionsPath = $questionDirectories['coreQuestion'];
            $selectedQuestionDirectories[] = $coreQuestionsPath;
        }
        if ($custom) {
            $customQuestionThemesPath = $questionDirectories['customCoreTheme'];
            $selectedQuestionDirectories[] = $customQuestionThemesPath;
        }
        if ($user) {
            $userQuestionThemesPath = $questionDirectories['customUserTheme'];
            if (!is_dir($userQuestionThemesPath)) {
                mkdir($userQuestionThemesPath);
            }
            $selectedQuestionDirectories[] = $userQuestionThemesPath;
        }

        if (isset($selectedQuestionDirectories)) {
            foreach ($selectedQuestionDirectories as $questionThemeDirectory) {
                $directory = new RecursiveDirectoryIterator($questionThemeDirectory);
                $iterator = new RecursiveIteratorIterator($directory);
                foreach ($iterator as $info) {
                    $ext = pathinfo($info->getPathname(), PATHINFO_EXTENSION);
                    if ($ext == 'xml') {
                        $questionDirectoriesAndPaths[$questionThemeDirectory][] = dirname($info->getPathname());
                    }
                }
            }
        }
        return $questionDirectoriesAndPaths;
    }


    /**
     * @param QuestionTheme $oQuestionTheme
     *
     * @return array
     */
    public static function uninstall($oQuestionTheme)
    {
        if (!Permission::model()->hasGlobalPermission('templates', 'delete')) {
            return false;
        }

        // if this questiontype is extended, it cannot be deleted
        if (empty($oQuestionTheme->extends)) {
            $aQuestionThemes = self::model()->findAll(
                'extends = :extends AND NOT id = :id',
                [
                    ':extends' => $oQuestionTheme->question_type,
                    ':id'      => $oQuestionTheme->id
                ]
            );
            if (!empty($aQuestionThemes)) {
                return [
                    'error'  => gT('Question type is being extended and cannot be uninstalled'),
                    'result' => false
                ];
            };
        }

        // transform theme name compatible with question attributes for core/default theme_template
        if (empty($oQuestionTheme->extends)) {
            $sThemeName = 'core';
        } else {
            $sThemeName = $oQuestionTheme->name;
        }

        // todo optimize function for very big surveys, eventually in yii 2 or 3 with batch processing / if this is breaking in Yii 1 use CDbDataReader
//        $query = new CDbDataReader($command);
//        $query->read();
        $aQuestions = Question::model()->with('questionAttributes')->findAll(
            'type = :type AND parent_qid = :parent_qid',
            [
                ':type' => $oQuestionTheme->question_type,
                ':parent_qid' => 0
            ]
        );
        foreach ( $aQuestions as $oQuestion) {
            if (isset($oQuestion['questionAttributes']['question_template'])) {
                if ($sThemeName == $oQuestion['questionAttributes']['question_template']) {
                    $bDeleteTheme = false;
                    break;
                };
            } else {
                if ($sThemeName == 'core') {
                    $bDeleteTheme = false;
                    break;
                };
            }
        }
        // if this questiontheme is used, it cannot be deleted
        if (isset($bDeleteTheme) && !$bDeleteTheme) {
            return [
                'error'  => gT('Question type is used in a Survey and cannot be uninstalled'),
                'result' => false
            ];
        }

        // delete questiontheme if it is not used
        try {
            return [
                'result' => $oQuestionTheme->delete()
            ];
        } catch (CDbException $e) {
            return [
                'error'  => $e->getMessage(),
                'result' => false
            ];
        }
    }

    /**
     * Returns All QuestionTheme settings
     *
     * @param string $question_type
     * @param string $language
     *
     * @return mixed $baseQuestions Questions as Array or Object
     */
    public static function findQuestionMetaData($question_type, $language = '')
    {
        $criteria = new CDbCriteria();
        $criteria->condition = 'extends = :extends';
        $criteria->addCondition('question_type = :question_type', 'AND');
        $criteria->params = [':extends' => '', ':question_type' => $question_type];

        $baseQuestion = self::model()->query($criteria, false, false);

        // language settings
        $baseQuestion['title'] = gT($baseQuestion['title'], "html", $language);
        $baseQuestion['group'] = gT($baseQuestion['group'], "html", $language);

        // decode settings json
        $baseQuestion['settings'] = json_decode($baseQuestion['settings']);

        return $baseQuestion;
    }

    /**
     * Returns all Question Meta Data for the selector
     *
     * @return mixed $baseQuestions Questions as Array or Object
     */
    public static function findAllQuestionMetaDataForSelector()
    {
        $criteria = new CDbCriteria();
        //            $criteria->condition = 'extends = :extends';
        $criteria->addCondition('visible = :visible', 'AND');
        $criteria->params = [':visible' => 'Y'];

        $baseQuestions = self::model()->query($criteria, true, false);

        $bOldEntityLoaderState = libxml_disable_entity_loader(true);
        $baseQuestionsModified = [];
        foreach ($baseQuestions as $baseQuestion) {
            //TODO: should be moved into DB column (question_theme_settings table)
            $sQuestionConfigFile = file_get_contents(App()->getConfig('rootdir') . DIRECTORY_SEPARATOR . $baseQuestion['xml_path'] . DIRECTORY_SEPARATOR . 'config.xml');  // @see: Now that entity loader is disabled, we can't use simplexml_load_file; so we must read the file with file_get_contents and convert it as a string
            $oQuestionConfig = simplexml_load_string($sQuestionConfigFile);
            $questionEngineData = json_decode(json_encode($oQuestionConfig->engine), true);
            $showAsQuestionType = $questionEngineData['show_as_question_type'];

            // if an extended Question should not be shown as a selectable questiontype skip it
            if (!empty($baseQuestion['extends'] && !$showAsQuestionType)) {
                continue;
            }

            // language settings
            $baseQuestion['title'] = gT($baseQuestion['title'], "html");
            $baseQuestion['group'] = gT($baseQuestion['group'], "html");

            // decode settings json
            $baseQuestion['settings'] = json_decode($baseQuestion['settings']);

            // if its a core question change name to core for rendering Default rendering in the selector
            if (empty($baseQuestion['extends'])) {
                $baseQuestion['name'] = 'core';
            }
            $baseQuestion['image_path'] = str_replace(
                '//',
                '/',
                App()->getConfig('publicurl') . $baseQuestion['image_path']
            );
            $baseQuestionsModified[] = $baseQuestion;
        }
        libxml_disable_entity_loader($bOldEntityLoaderState);
        $baseQuestions = $baseQuestionsModified;

        return $baseQuestions;
    }

    public static function getQuestionThemeDirectories()
    {
        $questionThemeDirectories['coreQuestion'] = App()->getConfig('corequestiontypedir') . '/survey/questions/answer';
        $questionThemeDirectories['customCoreTheme'] = App()->getConfig('userquestionthemedir');
        $questionThemeDirectories['customUserTheme'] = App()->getConfig('userquestionthemerootdir');

        return $questionThemeDirectories;
    }

    /**
     * Returns QuestionMetaData Array for use in ->save operations
     *
     * @param array $questionMetaData
     *
     * @return array $questionMetaData
     */
    private function getMetaDataArray($questionMetaData)
    {
        $questionMetaData = [
            'name'          => $questionMetaData['name'],
            'visible'       => 'Y', //todo
            'xml_path'      => $questionMetaData['xml_path'],
            'image_path'    => $questionMetaData['image_path'] ?? '',
            'title'         => $questionMetaData['title'],
            'creation_date' => date('Y-m-d H:i:s', strtotime($questionMetaData['creationDate'])),
            'author'        => $questionMetaData['author'],
            'author_email'  => $questionMetaData['authorEmail'],
            'author_url'    => $questionMetaData['authorUrl'],
            'copyright'     => $questionMetaData['copyright'],
            'license'       => $questionMetaData['license'],
            'version'       => $questionMetaData['version'],
            'api_version'   => $questionMetaData['apiVersion'],
            'description'   => $questionMetaData['description'],
            'last_update'   => date('Y-m-d H:i:s'), //todo
            'owner_id'      => 1, //todo
            'theme_type'    => $questionMetaData['type'],
            'question_type' => $questionMetaData['questionType'],
            'core_theme'    => $questionMetaData['coreTheme'],
            'extends'       => $questionMetaData['extends'],
            'group'         => $questionMetaData['group'] ?? '',
            'settings'      => $questionMetaData['settings'] ?? ''
        ];
        return $questionMetaData;
    }

    /**
     * Return the question Theme preview URL
     *
     * @param $sType : type of question
     *
     * @return string : question theme preview URL
     */
    public static function getQuestionThemeImageName($sType = null)
    {
        if ($sType == '*') {
            $preview_filename = 'EQUATION.png';
        } elseif ($sType == ':') {
            $preview_filename = 'COLON.png';
        } elseif ($sType == '|') {
            $preview_filename = 'PIPE.png';
        } elseif (!empty($sType)) {
            $preview_filename = $sType . '.png';
        } else {
            $preview_filename = '.png';
        }

        return $preview_filename;
    }

    /**
     * Returns the table definition for the current Question
     *
     * @param string $name
     * @param string $type
     *
     * @return string mixed
     */
    public static function getAnswerColumnDefinition($name, $type)
    {
        // cache the value between function calls
        static $cacheMemo = [];
        $cacheKey = $name . '_' . $type;
        if (isset($cacheMemo[$cacheKey])) {
            return $cacheMemo[$cacheKey];
        }

        if ($name == 'core') {
            $questionTheme = self::model()->findByAttributes([], 'question_type=:question_type AND extends=:extends', ['question_type' => $type, 'extends' => '']);
        } else {
            $questionTheme = self::model()->findByAttributes([], 'name=:name AND question_type=:question_type', ['name' => $name, 'question_type' => $type]);
        }

        $answerColumnDefinition = '';
        if (isset($questionTheme['xml_path'])) {
            $bOldEntityLoaderState = libxml_disable_entity_loader(true);

            $sQuestionConfigFile = file_get_contents(App()->getConfig('rootdir') . DIRECTORY_SEPARATOR . $questionTheme['xml_path'] . DIRECTORY_SEPARATOR . 'config.xml');  // @see: Now that entity loader is disabled, we can't use simplexml_load_file; so we must read the file with file_get_contents and convert it as a string
            $oQuestionConfig = simplexml_load_string($sQuestionConfigFile);
            if (isset($oQuestionConfig->metadata->answercolumndefinition)) {
                // TODO: Check json_last_error.
                $answerColumnDefinition = json_decode(json_encode($oQuestionConfig->metadata->answercolumndefinition), true)[0];
            }

            libxml_disable_entity_loader($bOldEntityLoaderState);
        }

        $cacheMemo[$cacheKey] = $answerColumnDefinition;
        return $answerColumnDefinition;
    }

    /**
     * Returns the Config Path for the selected Question Type base definition
     *
     * @param string $type
     *
     * @return string Path to config XML
     * @throws CException
     */
    static public function getQuestionXMLPathForBaseType($type)
    {
        $oQuestionTheme = QuestionTheme::model()->findByAttributes([], 'question_type = :question_type AND extends = :extends', ['question_type' => $type, 'extends' => '']);
        if (empty($oQuestionTheme)) {
            throw new \CException("The Database definition for Questiontype: " . $type . " is missing");
        }
        $configXMLPath = App()->getConfig('rootdir') . '/' . $oQuestionTheme['xml_path'] . '/config.xml';

        return $configXMLPath;

    }
}
