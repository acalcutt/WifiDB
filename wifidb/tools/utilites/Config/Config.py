__author__ = 'pferland'
from ConfigParser import ConfigParser
import os


class Config:
    def __init__(self, file):
        self.Config = ConfigParser()
        self.file = os.path.normpath(file)
        self.Config.read(self.file) # folder, file

    def ConfigMap(self, section):
        dict1 = {}
        options = self.Config.options(section)
        for option in options:
            dict1[option] = self.Config.get(section, option)
        return dict1