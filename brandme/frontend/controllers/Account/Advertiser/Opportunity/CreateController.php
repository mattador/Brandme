<?php
namespace Frontend\Controllers\Account\Advertiser\Opportunity;

use Entities\Campaign;
use Frontend\Controllers\Account\AccountControllerBase;
use Frontend\Module;
use Frontend\Services\Campaign\Opportunity;
use Frontend\Services\Campaign\Pricing;
use Frontend\Services\CustomValidators\ExecutionDateValidator;
use Frontend\Services\Form\Campaign\Opportunity as Form;
use Frontend\Services\Form\MultiStep;
use Frontend\Services\Form\Upload\Image;
use Frontend\Widgets\Translate;
use Phalcon\Exception;
use Phalcon\Tag;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Url as UrlValidator;

/** @todo Use HTMLpurifier to sanitize malicious XSS which might creep through the summernote WYSIWYG JS plugin */

/**
 * WARNING THIS IS NOW CONSIDERED LEGACY CODE
 * WE NEED TO BE SWITCHING TO ANGULAR AS SOON AS PHYSICALLY POSSIBLE
 * A one action controller used to create an opportunity associated with a campaign.
 * $_SESSION['opportunity'][id_of_opportunity]
 * Class OpportunityController
 *
 * @package Frontend\Controllers\Account\Advertiser
 */
class CreateController extends AccountControllerBase implements MultiStep
{

    protected $idCampaign;
    protected $opportunity;
    protected $errors = [];
    protected $renderingHasBegun;

    protected function initialize()
    {
        parent::initialize();
        $this->initOpportunity();
    }

    /**
     * Creates or resumes session instance of opportunity
     * I had originally used a session bag store instead of using a raw $_SESSION variable,
     * but as of Phalcon v2.0 the behavior is different
     *
     * @return array|string
     */
    protected function initOpportunity()
    {
        if ($this->router->getActionName() == 'bitly') {
            return;
        }
        //unset($this->session->opportunity);
        $params = $this->router->getParams();
        if (!isset($params['idCampaign'])) {
            $this->redirect('/anunciante');
        }
        $campaign = Campaign::findFirst('id_factor = '.$this->get('id').' AND id = '.$params['idCampaign']);
        //test that campaign exists and belongs to user
        if (!$campaign) {
            unset($_SESSION['opportunity']);
            $this->redirect('/anunciante');
        }
        $this->campaign = $campaign;
        $this->idCampaign = $params['idCampaign'];
        if (!isset($_SESSION['opportunity'][$this->idCampaign])) {
            $_SESSION['opportunity'][$this->idCampaign] = [];
        }
        $this->opportunity = &$_SESSION['opportunity'][$this->idCampaign];
    }

    /**
     * Is current step dirty
     *
     * @return bool
     */
    public function isDirty()
    {
        if (count($this->errors)) {
            return true;
        }

        return false;
    }

    /**
     * Returns ordered array of opportunity creation steps
     *
     * @return array|bool
     */
    protected function getSteps()
    {
        /**
         * If the opportunity is a direct bid segmentation is skipped
         */
        if (isset($this->opportunity['instructions']['type']) && $this->opportunity['instructions']['type'] == 'direct') {
            $steps = array_flip(Form::$steps);
            unset($steps['segmentation']);
            $steps = array_keys($steps);
        } else {
            $steps = Form::$steps;
        }

        return $steps;
    }

    /**
     * Returns current step
     *
     * @return mixed
     */
    public function current()
    {
        $p = $this->getPost();
        if (isset($p['step'])) {
            return $p['step'];
        }
        $steps = array_diff_key(array_flip($this->getSteps()), $this->opportunity);

        return key($steps);
    }

    /**
     * Go to the next step, which maybe the current if it is dirty
     *
     * @return mixed
     */
    public function forward()
    {
        $steps = array_reverse($this->getSteps());
        $current = $this->current();
        foreach ($steps as $i => $step) {
            if ($step == $current) {
                return isset($steps[$i - 1]) ? $steps[$i - 1] : $steps[$i];
            }
        }
    }

    /**
     * Go one step back from current step
     *
     * @return mixed
     */
    public function back()
    {
        $steps = array_reverse($this->getSteps());
        $current = $this->current();
        foreach ($steps as $i => $step) {
            if ($step == $current) {
                return isset($steps[$i + 1]) ? $steps[$i + 1] : $steps[$i];
            }
        }
    }

    /**
     * Render step
     *
     * @return mixed
     */
    public function render($step = false)
    {
        if ($this->renderingHasBegun) {
            return;
        }
        if (!$step) {
            $step = $this->current();
        }
        if ($step == 'type') {
            $this->warning(
                Translate::_('Soon you will be able to create campaigns with photos and videos in your favorite social networks and blogs!')
            );
        }
        $this->populate($step);
        $this->renderingHasBegun = true;
        $this->view->pick('/Account/Advertiser/Opportunity/Create/form');
        $this->view->setVars(['step' => $step, 'opportunity' => $this->opportunity]);
        $this->addAssets(
            'css/brandme/advertiser-opportunity-create.css',
            'css/bootstrap-datetimepicker.css',
            'js/moment.js',
            'js/bootstrap-datetimepicker.js',
            'js/summernote/summernote.css',
            'js/summernote/summernote.js',
            'js/jquery.maskMoney.min.js',
            'js/brandme/advertiser-opportunity-create.js'
        );
    }

    /**
     * If step has already been filled out, prep variables sticky form style
     *
     * @return mixed
     */
    public function populate($step)
    {
        $vars = isset(Form::$expectParams[$step]) ? Form::$expectParams[$step] : [];
        if (isset($this->opportunity[$step])) {
            $vars = array_intersect_key($this->opportunity[$step] + $vars, $vars);
        } elseif (isset($this->opportunity['dirty'][$step])) {
            $vars = array_intersect_key($this->opportunity['dirty'][$step] + $vars, $vars);
        }
        if ($step == 'instructions' && isset($vars['image_logo'])) {
            $this->view->setVar('image_logo', $vars['image_logo']);
        }
        if ($step == 'segmentation') {
            $this->view->setVars(
                [
                    'segmentation' => Form::getSegmentationOptions()
                ]
            );

        }
        Tag::setDefaults($vars);
        $this->view->setVar('vars', $vars);
        if ($step == 'confirmation') {
            //send all params to frontend for revision
            $this->view->setVar('segmentation', Form::getSegmentationOptions());
            $opportunity = [];
            foreach (Form::$expectParams as $stp => $fields) {
                if ($stp == 'segmentation' && $this->eq($this->opportunity['instructions'], 'type', 'direct')) {
                    continue;
                }
                $opportunity[$stp] = array_intersect_key($this->opportunity[$stp] + $fields, $fields);
            }
            $this->view->opportunity = $opportunity;
        }
    }

    /**
     * Validate step
     *
     * @return mixed
     */
    public function validate()
    {
        $p = $this->getPost();
        switch ($p['step']) {
            case 'type':
                if (!(isset($p['type']) && in_array($p['type'], Form::$contentType))) {
                    $this->errors[] = 'Please select a valid content type';
                }
                break;
            case 'network':
                if (!(isset($p['network']) && in_array($p['network'], Form::$networks))) {
                    $this->errors[] = 'Please select a valid Social Network';
                }
                break;
            case 'instructions':
                if (!$this->ok($p, 'opportunity_name')) {
                    $this->errors[] = 'Please enter a valid opportunity name';
                }
                if (Module::getService('Campaign\Opportunity')
                    ->checkForNamingConflict($p['opportunity_name'], $this->get('id'), $this->idCampaign)
                ) {
                    $this->errors[] = 'You have already created an opportunity with that name';
                }
                if (!$this->ok($p, 'type')) {
                    $this->errors[] = 'Please select the type of opportunity (open/direct)';
                } elseif ($p['type'] == 'open') {
                    $p['max_offer'] = preg_replace('/[^\d\.]/', '', $p['max_offer']);
                    if (!$this->ok($p, 'max_offer') || !preg_match('/^\$?\d+(,\d{3})*\.?[0-9]?[0-9]?$/', $p['max_offer'])) {
                        $this->errors[] = 'Please enter a valid maximum offer';
                    } elseif ($this->ok($p, 'max_offer')
                        && $p['max_offer'] > Module::getService('Campaign\Opportunity')->getCreditLeftInCampaign($this->idCampaign)
                    ) {
                        $this->errors[] = 'You don\'t have enough credit left in this campaign to allow for the maximum bid offer';
                    }
                }
                if (isset($p['about_opportunity'])) {
                    $p['about_opportunity'] = $this->request->getPost('about_opportunity');
                }
                if (!$this->ok($p, 'about_opportunity')) {
                    $this->errors[] = 'Please enter a valid description';
                }
                if (isset($p['requirements'])) {
                    $p['requirements'] = $this->request->getPost('requirements');
                }
                if (!$this->ok($p, 'requirements')) {
                    $this->errors[] = 'Please enter valid requirements';
                }
                if (isset($p['ideal_candidate'])) {
                    $p['ideal_candidate'] = $this->request->getPost('ideal_candidate');
                }
                if (!$this->ok($p, 'ideal_candidate')) {
                    $this->errors[] = 'Please describe a valid candidate';
                }
                if (isset($p['link_required']) && !strlen(trim(preg_replace('/http:\/\//', '', $p['link'])))) {
                    $this->errors[] = 'A valid link value is required';
                }
                if (isset($p['hashtag_required']) && !strlen(trim(preg_replace('/#/', '', $p['hashtag'])))) {
                    $this->errors[] = 'A valid hashtag value is required';
                }
                if (isset($p['mention_required']) && !strlen(trim(preg_replace('/@/', '', $p['mention'])))) {
                    $this->errors[] = 'A valid mention value is required';
                }
                $hasUploadedLogo = false;
                if (isset($p['MAX_FILE_SIZE'])) {
                    foreach ($this->request->getUploadedFiles() as $file) {
                        if ($file->getError() == UPLOAD_ERR_NO_FILE) {
                            continue;
                        }
                        if ($file->getKey() == 'image_logo') {
                            $hasUploadedLogo = true;
                            $uploaded = Image::upload($file, [150, 150, 128, 128], '/content/opportunity/logo');
                            if (is_array($uploaded)) {
                                $this->errors = array_merge($this->errors, $uploaded);
                            } elseif ($uploaded) {
                                $p[$file->getKey()] = $uploaded;
                            }
                            break;
                        }
                    }
                }
                if (!$hasUploadedLogo) {
                    if (isset($this->opportunity['instructions']['image_logo'])) {
                        $p['image_logo'] = $this->opportunity['instructions']['image_logo'];
                    } elseif (isset($this->opportunity['dirty']['instructions']['image_logo'])) {
                        $p['image_logo'] = $this->opportunity['dirty']['instructions']['image_logo'];
                    } else {
                        $this->errors[] = 'An image is required in order to impulse your opportunity correctly';
                    }
                }
                $validation = new Validation();
                $postValidate = [];
                /*
                 * To make the start date more flexible we allow the advertiser to put ANY date which is valid...including past dates.
                 * If the advertiser chooses a start dateThis just means we start inviting creators to participate the campaign straight away once approved.
                 */
                $validation->add('date', new ExecutionDateValidator(['message' => 'The start date is invalid']));
                $postValidate['date'] = $p['start_opportunity'];

                if ($this->ok($p, 'link')) {
                    $validation->add('link', new UrlValidator(['message' => 'The link url is invalid']));
                    $postValidate['link'] = $p['link'];
                }
                $validationMessages = $validation->validate($postValidate);
                if ($validationMessages->count()) {
                    foreach ($validationMessages as $msg) {
                        $this->errors[] = $msg->getMessage();
                    }
                }
                break;
            case 'segmentation':
                //The advertiser must meet a minimum criteria of segmentation
                $minimumRequired = 3; //I just chose an arbitrary value which I consider sufficient to properly segment users
                if (count(array_intersect_key(array_filter($p), Form::$expectParams['segmentation'])) < $minimumRequired) {
                    $this->errors[] = 'Your opportunity requires more segmentation in order to be properly targeted at content creators.';
                }
                /*if ($this->ok($p, 'postcode') && !intval(Region::count('postcode = "' . $p['postcode'] . '"'))) {
                    $this->errors[] = 'The postcode entered is invalid';
                }*/
                break;
            case 'confirmation':
                //remove from creation session here
                foreach ($this->getSteps() as $st) {
                    if (!isset($this->opportunity[$st]) && !$this->eq($p, 'step', 'confirmation')) {
                        $this->errors[] = 'Please complete each step';
                        $this->render($st);

                        return;
                    } else {
                        $opportunity = new Opportunity();
                        $opportunity->create($this->idCampaign, $this->opportunity, $this->get('id'));
                        unset($_SESSION['opportunity'][$this->idCampaign]);
                        $this->success(Translate::_('Opportunity created successfully'));
                        $this->redirect('/anunciante/campana/'.$this->idCampaign.'/oportunidades');
                    }
                }
                break;
            default:
                //destroy+restart opportunity here
                $this->redirect('/anunciante');
        }
        if (!$this->isDirty()) {
            $this->opportunity[$p['step']] = $p;
            //clean up dirty array if step passed validation
            unset($this->opportunity['dirty'][$p['step']]);
        } else {
            $this->opportunity['dirty'][$p['step']] = isset($this->opportunity['dirty'][$p['step']]) ? array_merge(
                $this->opportunity['dirty'][$p['step']],
                $p
            ) : $p;
        }
    }

    /**
     * Creates a new opportunity
     *
     * @param int $idCampaign
     * @throws Exception
     */
    public function indexAction($idCampaign)
    {
        if (Module::getService('Campaign/Opportunity')->getCreditLeftInCampaign($idCampaign) < Pricing::STANDARD_MARKUP) {
            $this->error(Translate::_('There is not enough credit left in this campaign to create a new opportunity'));
            $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidades');
        }
        $this->view->setVars(
            [
                'id' => $idCampaign,
                'steps' => array_merge(
                    array_keys($this->opportunity),
                    array_keys(isset($this->opportunity['dirty']) ? $this->opportunity['dirty'] : [])
                )
            ]
        );
        // Don't hate me :)
        // This is "another" temporary measure since I did not have time to convert a certain somebodies terrible HTML to AngularJs
        if ($this->request->isPost()) {
            $p = $this->getPost();
            if (!$this->security->checkToken()) {
                $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidad/crear');
            }
            if (isset($p['to'])) {
                switch ($p['to']) {
                    case 'back':
                        $this->render($this->back());

                        return;
                    case 'next':
                        $this->validate();
                        if ($this->isDirty()) {
                            $this->view->setVar('messages', $this->errors);
                            $this->render($this->current());

                            return;
                        }
                        $this->render($this->forward());
                        break;
                    case 'type';
                        $this->render('type');

                        return;
                    case 'network';
                        $this->render('network');

                        return;
                    case 'instructions';
                        $this->render('instructions');

                        return;
                    case 'segmentation';
                        $this->render('segmentation');

                        return;
                    case 'confirmation':
                        $this->render('confirmation');

                        return;
                    default:
                        $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidad/crear');
                }
            }
        }
        //if opportunity is empty it means user has just started
        $this->render(empty($this->opportunity) || empty($p) ? $this->current() : $this->forward());
    }

    /**
     * Consumes Bit.ly API (check composer dependency)
     *
     * @return \Phalcon\Http\Response|\Phalcon\HTTP\ResponseInterface
     */
    public function bitlyAction()
    {
        if (!$this->request->isAjax() || !$this->request->isPost()) {
            $this->redirect('/anunciante');
        }
        //both PHP's native filter_var and Phalcon's URL validator are way too permissive because they accept URL's with truncated hostnames. i.e. w/o .com .something etc
        $post = $this->getPost();
        $parsedUrl = parse_url($post['url']);
        if (!isset($parsedUrl['host']) || count(explode('.', $parsedUrl['host'])) == 1) {
            $this->response->setJsonContent(['error']);

            return $this->response;
        }
        $validator = new Validation();
        $validator->add('url', new UrlValidator());
        $result = [];
        if ($validator->validate(['url' => $post['url']])->count() > 0) {
            $result['error'] = true;
        } else {
            /**
             * A 500 error may be thrown by Bit.ly, which we can't control, so it gets suppressed along with other errors in index.php, and logged of course.
             */
            require APPLICATION_VENDOR_DIR.'/autoload.php';
            $bitly = new \Hpatoio\Bitly\Client($this->getConfig('bitly')->access_token);
            $url = $bitly->Shorten(['longUrl' => $post['url']]);
            $result['error'] = false;
            $result['url'] = $url['url'];
        }
        $this->response->setJsonContent($result);

        return $this->response;
    }

}