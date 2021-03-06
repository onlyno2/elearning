<?php
    function is_multiple_array(array $arr) 
    {
        foreach ($arr as $rows) {
            if(!is_array($rows)) 
                return false;
        }
        return true;
    }

    function check_valid_values($fields, $values) 
    {
        $numFields = count($fields);
        foreach($values as $rows) {
            if(count($rows) != $numFields) {
                return false;
            }
        } 
        return true;
    }

    function round_half_integer(float $float) 
    {
        return floor($float * 2) / 2;
    }
    
    function print_rating($rate, $num) 
    {   
        echo '<div class="row" style="padding-top: 10px; align-content: center; margin-left: 20px;">';
        echo '<span style="font-size: 16px; padding-right: 5px;">'.round($rate).'</span>';
        $star = round_half_integer($rate);
        $counter = $star;
        for($i = 0; $i < 5; $i++) {
            if($counter >= 1) {
                echo '<span class="glyphicon glyphicon-star" style="font-size: 18px; margin-top: 5px; padding-right: 2px; color: gold;"></span>';
                $counter -= 1;
            } else if($counter == 0.5) {
                echo '<span class="glyphicon glyphicon-star half" style="font-size: 18px; margin-top: 5px; padding-right: 2px; color: gold;"></span>';
                $counter -= 0.5;
            } else {
                echo '<span class="glyphicon glyphicon-star-empty" style="font-size: 18px; margin-top: 5px; padding-right: 2px; color: gold;"></span>';
            }
             
        }
        echo '<span class="course-ratings-count" style="font-size: 16px; margin-left: 5px;">   ('.$num.' vote)</span></div>';
    }

    function upload_image($image) 
    {
        $filename = $image['tmp_name'];
        $client_id = "3d396ef6747fb6b";
        $handle = fopen($filename, "r");
        $data = fread($handle, filesize($filename));
        $pvars   = array(
            'image' => base64_encode($data)
          );
        $timeout = 30;
        
        // khởi tạo tiến trình upload
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_URL, 'https://api.imgur.com/3/image.json');
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Client-ID ' . $client_id));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $pvars);
        
        // thực thi tiến trình upload ảnh
        $out = curl_exec($curl);
        curl_close($curl);

        $pms = json_decode($out, true);
        $url = $pms['data']['link'];
        if($url != "") {
            return $url;
        }
        else return false;
    }
    
    function course_list($courses) 
    {
        $output = "";
        $count = 0;
        if(is_array($courses)) {
            foreach($courses as $row) {
                if($count == 0) {
                    $output .= $row['course_id']; 
                    $count++;
                } else {
                    $output .= "," . $row['course_id'];
                }
            }
        } else {
            $output .= $courses[0];
        }
        return $output;
    } 

    function format_info($info)
    {
        $formated_info = array();
        
        if(is_array($info) || is_object($info)) {
            foreach($info as $row) {
                $num = $row['course_id'];
                $formated_info[$num] = $row;
            }
        }

        return $formated_info;
    }

    function all_course_printer($conn, $teacher_id) 
    {
        // get infomation about courses
        // get all courses
        $query = 'SELECT * FROM "Course" WHERE course_id IN (SELECT course_id FROM "AssignTeacher" WHERE teacher_id =' . "$teacher_id)";
        $result = pg_query($conn, $query);
        $courses = pg_fetch_all($result);
        if(pg_num_rows($result) > 0) {
            // // get all courses info
            $query = 'SELECT * FROM "Course" WHERE course_id IN ('. course_list($courses) . ')';
            $result = pg_query($conn, $query);
            $info = pg_fetch_all($result);  
            $courses_info = format_info($info);

            // // get all courses rating
            $query = 'SELECT course_id, AVG(rate), count(rate) FROM "Vote" GROUP BY course_id HAVING course_id IN (' .course_list($courses) . ')';
            $info = pg_fetch_all(pg_query($conn, $query));
            $courses_rating = format_info($info);

            //if(!empty($courses_rating[1])) print_r($courses_rating[1]);

            // begin to print
            foreach($courses_info as $key => $value) {
                $time = strtotime($value['created_at']);
                echo '<!-- .col -->
                <div class="course-content flex flex-wrap justify-content-between align-content-lg-stretch">
                    <figure class="course-thumbnail">
                        <a href="single_course.php?id='.$value['course_id'].'"><img src='. $value['avatar'] .' alt=""></a>
                    </figure>
                    <!-- .course-thumbnail -->
                    <div class="course-content-wrap">
                        <header class="entry-header">
                            <h2 class="entry-title"><a href="single_course.php?id='.$value['course_id'].'">'.$value['name'].'</a></h2>
                            <div class="entry-meta flex flex-wrap align-items-center">
                            <div class="course-date">' . date("M", $time) . " " . date("d", $time) . " " . date("Y", $time) . '</div>
                            </div>
                            <!-- .course-date -->
                        </header>
                        <!-- .entry-header -->
                        <footer class="entry-footer flex justify-content-between align-items-center">
                        <div class="row">
                            <div class="course-cost">
                            $'.$value['price'] * (1 - $value['discount']);
                            if($value['price'] * (1 - $value['discount']) <  $value['price'])
                                echo '<span class="price-drop">$'. $value['price'] .'</span>';
                            
                            echo   '</div> <!-- .course-cost -->
                        </div> <br><!-- row -->
                        <div class="course-ratings flex align-items-center">
                        ';
                        if(isset($courses_rating[$key])) {
                            print_rating($courses_rating[$key]['avg'], $courses_rating[$key]['count']);
                        } else {
                            print_rating(0, 0);
                        }
                        echo ' 
                        </div> <!-- .course-ratings -->

                        </footer>
                        <!-- .entry-footer -->
                    </div>
                    <!-- .course-content-wrap -->
                </div>
                <!-- course content -->';

            }
        }
    }

    function print_a_course($course_info) 
    {
        $time = strtotime($course_info['created_at']);
        echo '<div class="col-md-6">';
        echo '<div class="course-content flex flex-wrap justify-content-between align-content-lg-stretch">';
        echo '<figure class="course-thumbnail">';
        echo '<a href="single_course.php?id='.$course_info['course_id'].'"><img src='. $course_info['avatar'] .' alt=""></a>';
        echo '</figure>';
        echo '<!-- .course-thumbnail -->';
                echo   '<div class="course-content-wrap">';
                        echo '<header class="entry-header">';
                            echo '<h2 class="entry-title"><a href="single_course.php?id='.$course_info['course_id'].'">'.$course_info['name'].'</a></h2>';
                            echo '<div class="entry-meta flex flex-wrap align-items-center">';
                            echo '<div class="course-date">' . date("M", $time) . " " . date("d", $time) . " " . date("Y", $time) . '</div>';
                            echo '</div>';
                            echo '<!-- .course-date -->';
                        echo '</header>';
                        echo '<!-- .entry-header -->';
                        echo '<footer class="entry-footer flex justify-content-between align-items-center">';
                        echo '<div class="row">';
                            echo '<div class="course-cost">';
                            echo '<span style="font-size:17px;">$'.$course_info['price'] * (1 - $course_info['discount']) .'</span>';
                            if($course_info['price'] * (1 - $course_info['discount']) <  $course_info['price'])
                                echo '<span class="price-drop" style="font-size:16px">$'. $course_info['price'] .'</span>';
                            
                            echo   '</div> <!-- .course-cost -->
                        </div> <br><!-- row -->
                        <div class="course-ratings flex align-items-center">
                        ';
                        print_rating($course_info['avg'], $course_info['count']);
                        echo ' 
                        </div> <!-- .course-ratings -->

                        </footer>
                        <!-- .entry-footer -->
                    </div>
                    <!-- .course-content-wrap -->
                </div>
                <!-- course content -->';
                echo '</div>';
    }
?>