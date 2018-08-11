<?php

namespace Hammer\WH\Model;

class Handles extends \Magento\Framework\View\Model\Layout\Merge
{
    /**
     * Display all handles at the top of the page
     * @param array|string $handleName
     * @return $this|\Magento\Framework\View\Model\Layout\Merge
     */
    public function addHandle($handleName)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeInfo = $objectManager->create('\Hammer\WH\Settings\StoreInfo');
        $displayHandles = $storeInfo->getDisplayHandles();

        if (is_array($handleName)) {
            foreach ($handleName as $name) {
                if($displayHandles) {
                    echo '<div class="hammer_classes">'.$name.'</div>';
                }
                $this->handles[$name] = 1;
            }
        } else {
            if($displayHandles) {
                echo '<div class="hammer_classes">'.$handleName.'</div>';
            }
            $this->handles[$handleName] = 1;
        }
        return $this;
    }
}

