<?php
/**
 * Export YouTube Channel including private or otherwise hidden videos.
 * Class Atv_Export_Channel
 */
Class Atv_ExportChannel
{
    /**
     * @var Zend_Gdata_YouTube
     */
    public $youtubeClient;

    /**
     * Connect to youtube using impersonation.
     * @param $config
     * @return $this
     */
    public function youtubeConnect($config){
        $httpClient = Zend_Gdata_ClientLogin::getHttpClient(
            $config['username'],
            $config['passwd'],
            'youtube'
        );

        $youtubeClient = new Zend_Gdata_YouTube(
            $httpClient,
            null,
            null,
            $config['server-api-key']
        );

        $youtubeClient->setMajorProtocolVersion(2);
        $this->youtubeClient = $youtubeClient;
        return $this;
    }

    /**
     * Fetch number of videos uploaded by user into default channel
     * @return mixed
     */
    public function fetchUploadCount(){
        $userProfile = $this->youtubeClient->getUserProfile('default');
        return $userProfile->getFeedLink('http://gdata.youtube.com/schemas/2007#user.uploads')->countHint;
    }

    /**
     * Fetch uploaded videos from users default channel.
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function fetchDefaultVideoList($offset=0,$limit=20){
        $url = Zend_Gdata_YouTube::USER_URI .'/default/'. Zend_Gdata_YouTube::UPLOADS_URI_SUFFIX;
        $location = new Zend_Gdata_YouTube_VideoQuery($url);
        $location->setStartIndex($offset);
        $location->setMaxResults($limit);
        $videoFeed = $this->youtubeClient->getVideoFeed($location);
        return $videoFeed;
    }

    /**
     * Save a defined set of the video's properties to an array.
     * @param $videoEntry
     * @return array
     */
    public function videoEntryToArray($videoEntry){
            $arrVideoProperties = array();

            $arrVideoProperties['id'] = $videoEntry->getVideoId();
            $arrVideoProperties['title'] = $videoEntry->getVideoTitle();
            $arrVideoProperties['description'] = $videoEntry->getVideoDescription();

            $arrVideoProperties['category'] = $videoEntry->getVideoCategory();
            $arrVideoProperties['tags'] = $videoEntry->getVideoTags();
            $arrVideoProperties['geo-location'] = $videoEntry->getVideoGeoLocation();

            $arrVideoProperties['date-updated'] = (string) $videoEntry->getUpdated();
            $arrVideoProperties['date-recorded'] = (string) $videoEntry->getVideoRecorded();
            $arrVideoProperties['duration'] = $videoEntry->getVideoDuration();
            $arrVideoProperties['rating'] = $videoEntry->getVideoRatingInfo();
            $arrVideoProperties['view-count'] = $videoEntry->getVideoViewCount();

            $arrVideoProperties['link'] = $videoEntry->getVideoWatchPageUrl();
            $arrVideoProperties['link-flash'] = $videoEntry->getFlashPlayerUrl();

            $videoThumbnails = $videoEntry->getVideoThumbnails();
            foreach($videoThumbnails as $videoThumbnail) {
                $tmp = array();
                $tmp['time'] = $videoThumbnail['time'];
                $tmp['url'] = $videoThumbnail['url'];
                $tmp['height'] = $videoThumbnail['height'];
                $tmp['width'] = $videoThumbnail['width'];
                $arrVideoProperties['thumbnails'][] = $tmp;
            }

            return $arrVideoProperties;
    }

    /**
     * Export all uploaded videos as an array. Array is indexed by the video ID.
     * @param $config
     * @return array
     */
    public function exportDefaultChannel($config){
        $videos = array();
        $this->youtubeConnect($config);
        $videoCount = $this->fetchUploadCount();
        if($videoCount > 0){
            //youtube currently has a limit of 25, recently lowered from 30.
            $intLimitPerPage = 20;
            //calculate # of pages need to retrieve whole channel.
            $intTotalPages = round($videoCount / $intLimitPerPage,PHP_ROUND_HALF_UP);
            for($intPage=0;$intPage <=  $intTotalPages; $intPage++){
                $videoList = $this->fetchDefaultVideoList(($intPage*$intLimitPerPage),$intLimitPerPage);
                foreach($videoList as $videoEntry){
                    $tmpVideo = $this->videoEntryToArray($videoEntry);
                    $videos[$tmpVideo['id']] = $tmpVideo;
                }
            }
        }
        return $videos;
    }
}