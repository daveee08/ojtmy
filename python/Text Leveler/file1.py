from db_utils import insert_session_and_message

grade_level = "Grade 5"
learning_speed = "Normal"

user_id = 1
agent_id = 4
sender = "human"
topic = "Sample Topic"

insert_session_and_message(user_id, agent_id, sender, topic, locals())
