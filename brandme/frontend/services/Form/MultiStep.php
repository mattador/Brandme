<?php

namespace Frontend\Services\Form;

interface MultiStep
{

    /**
     * If step has already been filled out, prep variables sticky form style
     *
     * @param $step
     * @return mixed
     */
    function populate($step);

    /**
     * Validate step
     *
     * @return mixed
     */
    function validate();

    /**
     * Is current step dirty
     *
     * @return mixed
     */
    function isDirty();

    /**
     * Render step
     *
     * @return mixed
     */
    function render($step = false);

    /**
     * Returns current step
     *
     * @return mixed
     */
    function current();

    /**
     * Go to the next step, which maybe the current if it is dirty
     * @return mixed
     */
    function forward();

    /**
     * Go one step back from current step
     *
     * @return mixed
     */
    function back();
}