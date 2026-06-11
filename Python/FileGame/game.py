import pygame, sys, random

# ======= Hàm xử lý game =======
def draw_floor(): # Vẽ mặt đất
    screen.blit(floor, (floor_x_pos, 650))
    screen.blit(floor, (floor_x_pos + 432, 650))

def create_pipe(): # Tạo ống
    random_pipe_pos = random.randint(200, 400) 
    bottom_pipe = pipe_surface.get_rect(midtop=(500, random_pipe_pos))
    top_pipe = pipe_surface.get_rect(midtop=(500, random_pipe_pos - 700))
    return bottom_pipe, top_pipe

def move_pipe(pipes):  # Di chuyển ống
    for pipe in pipes:
        pipe.centerx -= 4
    return pipes

def draw_pipe(pipes):  # Vẽ ống
    for pipe in pipes: 
        if pipe.bottom >= 600:
            screen.blit(pipe_surface, pipe)
        else:
            flip_pipe = pygame.transform.flip(pipe_surface, False, True)
            screen.blit(flip_pipe, pipe)

def check_collision(pipes):  # Kiểm tra va chạm
    for pipe in pipes:
        if bird_rect.colliderect(pipe):
            hit_sound.play()
            return False
    if bird_rect.top <= -75 or bird_rect.bottom >= 650:
        return False
    return True

def rotate_bird(bird1):  # Xoay chim
    new_bird = pygame.transform.rotozoom(bird1, -bird_movement * 3, 1)
    return new_bird

def bird_animation():  # Hoạt ảnh chim
    new_bird = bird_list[bird_index] 
    new_bird_rect = new_bird.get_rect(center=(100, bird_rect.centery))
    return new_bird, new_bird_rect

def score_display(game_state):  # Hiển thị điểm
    if game_state == 'main game':
        score_surface = game_font.render(str(int(score)), True, (255, 255, 255))
        score_rect = score_surface.get_rect(center=(216, 100))
        screen.blit(score_surface, score_rect)
    if game_state == 'game_over':
        score_surface = game_font.render(f'Score: {int(score)}', True, (255, 255, 255))
        score_rect = score_surface.get_rect(center=(216, 100))
        screen.blit(score_surface, score_rect)

        high_score_surface = game_font.render(f'High Score: {int(high_score)}', True, (255, 255, 255))
        high_score_rect = high_score_surface.get_rect(center=(216, 630))
        screen.blit(high_score_surface, high_score_rect)

def update_score(score, high_score):  # Cập nhật điểm cao
    if score > high_score:
        high_score = score
    return high_score

# ======= Khởi tạo game =======
pygame.mixer.pre_init(frequency=44100, size=-16, channels=2, buffer=512)
pygame.init()
screen = pygame.display.set_mode((432, 768))
clock = pygame.time.Clock()
game_font = pygame.font.Font('Python/FileGame/04B_19.TTF', 35)

# ======= Biến trò chơi =======
gravity = 0.35  # Trọng lực
bird_movement = 0  # Chuyển động của chim
game_active = True  # Trạng thái trò chơi
score = 0  # Điểm hiện tại
high_score = 0  # Điểm cao nhất

# ======= Load ảnh =======
bg = pygame.image.load('Python/FileGame/assets/background-night.png').convert()
bg = pygame.transform.scale2x(bg)

floor = pygame.image.load('Python/FileGame/assets/floor.png').convert()
floor = pygame.transform.scale2x(floor)
floor_x_pos = 0

bird_down = pygame.transform.scale2x(pygame.image.load('Python/FileGame/assets/yellowbird-downflap.png').convert_alpha())
bird_mid = pygame.transform.scale2x(pygame.image.load('Python/FileGame/assets/yellowbird-midflap.png').convert_alpha())
bird_up = pygame.transform.scale2x(pygame.image.load('Python/FileGame/assets/yellowbird-upflap.png').convert_alpha())
bird_list = [bird_down, bird_mid, bird_up]
bird_index = 0
bird = bird_list[bird_index]
bird_rect = bird.get_rect(center=(100, 384))

# ======= Timer chim flap =======
birdflap = pygame.USEREVENT + 1
pygame.time.set_timer(birdflap, 200)

# ======= Ống =======
pipe_surface = pygame.image.load('Python/FileGame/assets/pipe-green.png').convert()
pipe_surface = pygame.transform.scale2x(pipe_surface)
pipe_list = [] 

spawnpipe = pygame.USEREVENT
pygame.time.set_timer(spawnpipe, 1200)

# ======= Màn hình kết thúc =======
game_over_surface = pygame.transform.scale2x(pygame.image.load('Python/FileGame/assets/message.png').convert_alpha())
game_over_rect = game_over_surface.get_rect(center=(216, 384))

# ======= Âm thanh =======
flap_sound = pygame.mixer.Sound('Python/FileGame/sound/sfx_wing.wav')
hit_sound = pygame.mixer.Sound('Python/FileGame/sound/sfx_hit.wav')
score_sound = pygame.mixer.Sound('Python/FileGame/sound/sfx_point.wav')
score_sound_countdown = 100

# ======= Vòng lặp game chính =======
while True:
    for event in pygame.event.get():
        if event.type == pygame.QUIT:
            pygame.quit()
            sys.exit()
        if event.type == pygame.KEYDOWN:
            if event.key == pygame.K_SPACE and game_active:
                bird_movement = 0
                bird_movement = -7.0
                flap_sound.play()
            if event.key == pygame.K_SPACE and not game_active:
                game_active = True 
                pipe_list.clear()  
                bird_rect.center = (100, 384) 
                bird_movement = 0
                score = 0  
        if event.type == spawnpipe:  
            pipe_list.extend(create_pipe())
        if event.type == birdflap:  
            if bird_index < 2:
                bird_index += 1
            else:
                bird_index = 0
            bird, bird_rect = bird_animation()

    screen.blit(bg, (0, 0))

    if game_active:
        # Chim
        bird_movement += gravity 
        rotated_bird = rotate_bird(bird)
        bird_rect.centery += bird_movement
        screen.blit(rotated_bird, bird_rect)  
        game_active = check_collision(pipe_list)  
        # Ống
        pipe_list = move_pipe(pipe_list)  
        draw_pipe(pipe_list) 
        # Điểm
        score += 0.01  
        score_display('main game') 
        score_sound_countdown -= 1
        if score_sound_countdown <= 0: 
            score_sound.play()
            score_sound_countdown = 100
    else:  
        screen.blit(game_over_surface, game_over_rect)  
        high_score = update_score(score, high_score)  
        score_display('game_over')  

    floor_x_pos -= 1  
    draw_floor()  
    if floor_x_pos <= -432:  
        floor_x_pos = 0

    pygame.display.update() 
    clock.tick(60)  